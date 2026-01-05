<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\SystemLogService;

class contract extends Model
{
    use HasFactory;

    const BONUS_COEFFICIENT = 0.5; // 50% of contract PV goes to inviter as bonus

    protected $fillable = [
        'codice_contratto',
        'inserito_da_user_id',
        'associato_a_user_id',
        'product_id',
        'customer_data_id',
        'data_inserimento',
        'data_stipula',
        'payment_mode_id',
        'status_contract_id',
    ];

    // ==================== RELATIONSHIPS ====================

    public function User()
    {
        return $this->belongsTo(User::class, 'associato_a_user_id');
    }

    public function UserSeu()
    {
        return $this->belongsTo(User::class, 'inserito_da_user_id');
    }

    public function customer_data()
    {
        return $this->belongsTo(customer_data::class);
    }

    public function status_contract()
    {
        return $this->belongsTo(status_contract::class);
    }

    public function product()
    {
        return $this->belongsTo(product::class);
    }

    public function specific_data()
    {
        return $this->hasMany(specific_data::class);
    }

    public function payment_mode()
    {
        return $this->belongsTo(payment_mode::class);
    }

    public function document_data()
    {
        return $this->belongsTo(document_data::class);
    }

    public function backofficeNote()
    {
        return $this->hasMany(backoffice_note::class);
    }

    public function ticket()
    {
        return $this->hasMany(Ticket::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log contract creation
        static::created(function ($contract) {
            SystemLogService::database()->info("Contract created", [
                'contract_id' => $contract->id,
                'contract_code' => $contract->codice_contratto,
                'inserted_by_user_id' => $contract->inserito_da_user_id,
                'associated_to_user_id' => $contract->associato_a_user_id,
                'product_id' => $contract->product_id,
                'customer_data_id' => $contract->customer_data_id,
                'status_contract_id' => $contract->status_contract_id,
            ]);
        });

        // Log contract updates and handle PV/PC assignment
        static::updated(function ($contract) {
            // Log all changes
            $changes = $contract->getChanges();
            $original = $contract->getOriginal();
            
            // Build changes array for logging
            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                SystemLogService::database()->info("Contract updated", [
                    'contract_id' => $contract->id,
                    'contract_code' => $contract->codice_contratto,
                    'changes' => $changesForLog,
                ]);
            }

            // Handle PV/PC assignment on status change
            if ($contract->isDirty('status_contract_id')) {
                static::handleStatusChange($contract);
            }
        });

        // Log contract deletion
        static::deleted(function ($contract) {
            SystemLogService::database()->warning("Contract deleted", [
                'contract_id' => $contract->id,
                'contract_code' => $contract->codice_contratto,
                'inserted_by_user_id' => $contract->inserito_da_user_id,
                'associated_to_user_id' => $contract->associato_a_user_id,
            ]);
        });
    }

    /**
     * Handle status change - PV/PC assignment logic
     */
    protected static function handleStatusChange($contract)
    {
        DB::beginTransaction();

        try {
            $oldStatusId = $contract->getOriginal('status_contract_id');
            $newStatusId = $contract->status_contract_id;

            // Get option_status for old and new status
            $oldOptionStatus = option_status_contract::where('status_contract_id', $oldStatusId)
                ->first();

            $newOptionStatus = option_status_contract::where('status_contract_id', $newStatusId)
                ->first();

            // Get contract user (SEU/advisor who gets the points)
            $user = User::find($contract->associato_a_user_id);

            if (!$user) {
                throw new \Exception("User {$contract->associato_a_user_id} not found for contract {$contract->id}");
            }

            // Get macro product points
            $macroProduct = $contract->product->macro_product ?? null;

            if (!$macroProduct) {
                SystemLogService::userActivity()->warning("Contract status change: No macro product found", [
                    'contract_id' => $contract->id,
                    'contract_code' => $contract->codice_contratto,
                    'product_id' => $contract->product_id,
                ]);
                DB::commit();
                return;
            }

            $puntiValore = $macroProduct->punti_valore ?? 0;
            $puntiCarriera = $macroProduct->punti_carriera ?? 0;

            // CASE 1: FROM non-genera_pv TO genera_pv (ASSIGN POINTS)
            if ((!$oldOptionStatus || !$oldOptionStatus->genera_pv) &&
                ($newOptionStatus && $newOptionStatus->genera_pv)) {

                static::assignPoints($contract, $user, $puntiValore, $puntiCarriera);
            }

            // CASE 2: FROM genera_pv TO non-genera_pv (REVOKE POINTS - STORNO/KO)
            elseif (($oldOptionStatus && $oldOptionStatus->genera_pv) &&
                (!$newOptionStatus || !$newOptionStatus->genera_pv)) {

                static::revokePoints($contract, $user, $puntiValore, $puntiCarriera);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            SystemLogService::system()->error("Error processing contract status change", [
                'contract_id' => $contract->id,
                'contract_code' => $contract->codice_contratto,
                'error' => $e->getMessage(),
            ], $e);
            throw $e;
        }
    }

    /**
     * Assign PV, PC and Bonus when contract becomes GETTONATO
     */
    protected static function assignPoints($contract, $user, $puntiValore, $puntiCarriera)
    {
        // 1. Assign PV and PC to the contract user (SEU/advisor)
        if ($puntiValore > 0) {
            $user->increment('punti_valore_maturati', $puntiValore);
        }

        if ($puntiCarriera > 0) {
            $user->increment('punti_carriera_maturati', $puntiCarriera);
        }

        SystemLogService::userActivity()->info("Points assigned to user", [
            'contract_id' => $contract->id,
            'contract_code' => $contract->codice_contratto,
            'user_id' => $user->id,
            'user_name' => $user->name . ' ' . $user->cognome,
            'punti_valore_assigned' => $puntiValore,
            'punti_carriera_assigned' => $puntiCarriera,
            'new_pv_balance' => $user->punti_valore_maturati,
            'new_pc_balance' => $user->punti_carriera_maturati,
        ]);

        // 2. Assign Bonus to inviter (if applicable)
        static::assignBonusToInviter($contract, $puntiValore);
    }

    /**
     * Revoke PV, PC and Bonus when contract goes to STORNO or KO
     */
    protected static function revokePoints($contract, $user, $puntiValore, $puntiCarriera)
    {
        // 1. Revoke PV and PC from the contract user
        if ($puntiValore > 0) {
            $user->decrement('punti_valore_maturati', $puntiValore);
        }

        if ($puntiCarriera > 0) {
            $user->decrement('punti_carriera_maturati', $puntiCarriera);
        }

        SystemLogService::userActivity()->warning("Points revoked from user", [
            'contract_id' => $contract->id,
            'contract_code' => $contract->codice_contratto,
            'user_id' => $user->id,
            'user_name' => $user->name . ' ' . $user->cognome,
            'punti_valore_revoked' => $puntiValore,
            'punti_carriera_revoked' => $puntiCarriera,
            'new_pv_balance' => $user->punti_valore_maturati,
            'new_pc_balance' => $user->punti_carriera_maturati,
        ]);

        // 2. Revoke Bonus from inviter (if applicable)
        static::revokeBonusFromInviter($contract, $puntiValore);
    }

    /**
     * Assign bonus points to the user who invited this contract's client
     */
    protected static function assignBonusToInviter($contract, $puntiValore)
    {
        if ($puntiValore <= 0) {
            return null;
        }

        // Check if contract client was converted from a lead
        $leadConverted = leadConverted::where('cliente_id', $contract->associato_a_user_id)->first();

        if (!$leadConverted) {
            SystemLogService::userActivity()->info("Contract bonus check: Client not from lead", [
                'contract_id' => $contract->id,
                'associated_user_id' => $contract->associato_a_user_id,
                'result' => 'no_bonus',
            ]);
            return null;
        }

        // Get the original lead
        $lead = lead::find($leadConverted->lead_id);

        if (!$lead || !$lead->invitato_da_user_id) {
            SystemLogService::userActivity()->info("Contract bonus check: Lead not invited by anyone", [
                'contract_id' => $contract->id,
                'lead_id' => $leadConverted->lead_id,
                'result' => 'no_bonus',
            ]);
            return null;
        }

        // Get the inviter user
        $inviter = User::find($lead->invitato_da_user_id);

        if (!$inviter) {
            SystemLogService::userActivity()->warning("Contract bonus check: Inviter not found", [
                'contract_id' => $contract->id,
                'inviter_user_id' => $lead->invitato_da_user_id,
                'result' => 'inviter_not_found',
            ]);
            return null;
        }

        // Calculate and assign bonus
        $bonusPoints = (int) round($puntiValore * self::BONUS_COEFFICIENT);
        $inviter->increment('punti_bonus', $bonusPoints);

        SystemLogService::userActivity()->info("Bonus assigned to inviter", [
            'contract_id' => $contract->id,
            'contract_code' => $contract->codice_contratto,
            'contract_pv' => $puntiValore,
            'bonus_coefficient' => self::BONUS_COEFFICIENT,
            'bonus_assigned' => $bonusPoints,
            'inviter_id' => $inviter->id,
            'inviter_name' => $inviter->name . ' ' . $inviter->cognome,
            'inviter_new_bonus_balance' => $inviter->punti_bonus,
        ]);

        return $bonusPoints;
    }

    /**
     * Revoke bonus points from inviter when contract is revoked
     */
    protected static function revokeBonusFromInviter($contract, $puntiValore)
    {
        if ($puntiValore <= 0) {
            return null;
        }

        // Check if contract client was converted from a lead
        $leadConverted = leadConverted::where('cliente_id', $contract->associato_a_user_id)->first();

        if (!$leadConverted) {
            return null;
        }

        // Get the original lead
        $lead = lead::find($leadConverted->lead_id);

        if (!$lead || !$lead->invitato_da_user_id) {
            return null;
        }

        // Get the inviter user
        $inviter = User::find($lead->invitato_da_user_id);

        if (!$inviter) {
            return null;
        }

        // Calculate and revoke bonus
        $bonusPoints = (int) round($puntiValore * self::BONUS_COEFFICIENT);
        $inviter->decrement('punti_bonus', $bonusPoints);

        SystemLogService::userActivity()->warning("Bonus revoked from inviter", [
            'contract_id' => $contract->id,
            'contract_code' => $contract->codice_contratto,
            'contract_pv' => $puntiValore,
            'bonus_revoked' => $bonusPoints,
            'inviter_id' => $inviter->id,
            'inviter_name' => $inviter->name . ' ' . $inviter->cognome,
            'inviter_new_bonus_balance' => $inviter->punti_bonus,
        ]);

        return $bonusPoints;
    }
}