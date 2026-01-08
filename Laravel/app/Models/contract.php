<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Critical fields that warrant warning level logging
     */
    protected static $criticalFields = [
        'status_contract_id',
        'associato_a_user_id',
        'product_id',
        'customer_data_id',
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
        // Log contract creation with entity tracking
        static::created(function ($contract) {
            $contract->load(['User', 'UserSeu', 'product', 'status_contract', 'customer_data']);

            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            $associatedUserName = $contract->User 
                ? $contract->User->name . ' ' . $contract->User->cognome 
                : null;

            $insertedByName = $contract->UserSeu 
                ? $contract->UserSeu->name . ' ' . $contract->UserSeu->cognome 
                : null;

            $productName = $contract->product?->descrizione;
            $statusName = $contract->status_contract?->micro_stato;
            $customerName = $contract->customer_data 
                ? trim(($contract->customer_data->nome ?? '') . ' ' . ($contract->customer_data->cognome ?? ''))
                : null;

            // Use forEntity and forContract for audit trail
            SystemLogService::userActivity()
                ->forEntity('contract', $contract->id)
                ->forContract($contract->id)
                ->info("Contract created", [
                    'contract_id' => $contract->id,
                    'contract_code' => $contract->codice_contratto,
                    'inserted_by_user_id' => $contract->inserito_da_user_id,
                    'inserted_by_name' => $insertedByName,
                    'associated_to_user_id' => $contract->associato_a_user_id,
                    'associated_to_name' => $associatedUserName,
                    'product_id' => $contract->product_id,
                    'product_name' => $productName,
                    'customer_data_id' => $contract->customer_data_id,
                    'customer_name' => $customerName,
                    'status_contract_id' => $contract->status_contract_id,
                    'status_name' => $statusName,
                    'data_inserimento' => $contract->data_inserimento,
                    'created_by' => $operatorName,
                ]);
        });

        // Log contract updates and handle PV/PC assignment
        static::updated(function ($contract) {
            // Log all changes with entity tracking
            $changes = $contract->getChanges();
            $original = $contract->getOriginal();
            
            // Build changes array for logging
            $changesForLog = [];
            $hasCriticalChanges = false;

            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];

                    if (in_array($field, static::$criticalFields)) {
                        $hasCriticalChanges = true;
                    }
                }
            }

            if (!empty($changesForLog)) {
                $operatorName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // Enrich log with readable names for status changes
                $logData = [
                    'contract_id' => $contract->id,
                    'contract_code' => $contract->codice_contratto,
                    'changes' => $changesForLog,
                    'critical_change' => $hasCriticalChanges,
                    'updated_by' => $operatorName,
                ];

                // Add status names if status changed
                if (isset($changesForLog['status_contract_id'])) {
                    $oldStatus = status_contract::find($changesForLog['status_contract_id']['old']);
                    $newStatus = status_contract::find($changesForLog['status_contract_id']['new']);
                    $logData['old_status_name'] = $oldStatus?->micro_stato;
                    $logData['new_status_name'] = $newStatus?->micro_stato;
                }

                // Use warning for critical changes
                $level = $hasCriticalChanges ? 'warning' : 'info';

                SystemLogService::userActivity()
                    ->forEntity('contract', $contract->id)
                    ->forContract($contract->id)
                    ->{$level}("Contract updated", $logData);
            }

            // Handle PV/PC assignment on status change
            if ($contract->isDirty('status_contract_id')) {
                static::handleStatusChange($contract);
            }
        });

        // Log contract deletion with entity tracking
        static::deleted(function ($contract) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::userActivity()
                ->forEntity('contract', $contract->id)
                ->forContract($contract->id)
                ->warning("Contract deleted", [
                    'contract_id' => $contract->id,
                    'contract_code' => $contract->codice_contratto,
                    'inserted_by_user_id' => $contract->inserito_da_user_id,
                    'associated_to_user_id' => $contract->associato_a_user_id,
                    'product_id' => $contract->product_id,
                    'status_contract_id' => $contract->status_contract_id,
                    'deleted_by' => $operatorName,
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
                SystemLogService::userActivity()
                    ->forEntity('contract', $contract->id)
                    ->forContract($contract->id)
                    ->warning("Contract status change: No macro product found", [
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
            SystemLogService::system()
                ->forEntity('contract', $contract->id)
                ->forContract($contract->id)
                ->error("Error processing contract status change", [
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

        SystemLogService::userActivity()
            ->forEntity('contract', $contract->id)
            ->forContract($contract->id)
            ->info("Points assigned to user", [
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

        SystemLogService::userActivity()
            ->forEntity('contract', $contract->id)
            ->forContract($contract->id)
            ->warning("Points revoked from user", [
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
            SystemLogService::userActivity()
                ->forEntity('contract', $contract->id)
                ->forContract($contract->id)
                ->info("Contract bonus check: Client not from lead", [
                    'contract_id' => $contract->id,
                    'associated_user_id' => $contract->associato_a_user_id,
                    'result' => 'no_bonus',
                ]);
            return null;
        }

        // Get the original lead
        $lead = lead::find($leadConverted->lead_id);

        if (!$lead || !$lead->invitato_da_user_id) {
            SystemLogService::userActivity()
                ->forEntity('contract', $contract->id)
                ->forContract($contract->id)
                ->info("Contract bonus check: Lead not invited by anyone", [
                    'contract_id' => $contract->id,
                    'lead_id' => $leadConverted->lead_id,
                    'result' => 'no_bonus',
                ]);
            return null;
        }

        // Get the inviter user
        $inviter = User::find($lead->invitato_da_user_id);

        if (!$inviter) {
            SystemLogService::userActivity()
                ->forEntity('contract', $contract->id)
                ->forContract($contract->id)
                ->warning("Contract bonus check: Inviter not found", [
                    'contract_id' => $contract->id,
                    'inviter_user_id' => $lead->invitato_da_user_id,
                    'result' => 'inviter_not_found',
                ]);
            return null;
        }

        // Calculate and assign bonus
        $bonusPoints = (int) round($puntiValore * self::BONUS_COEFFICIENT);
        $inviter->increment('punti_bonus', $bonusPoints);

        SystemLogService::userActivity()
            ->forEntity('contract', $contract->id)
            ->forContract($contract->id)
            ->info("Bonus assigned to inviter", [
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

        SystemLogService::userActivity()
            ->forEntity('contract', $contract->id)
            ->forContract($contract->id)
            ->warning("Bonus revoked from inviter", [
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

    // ==================== HELPER METHODS ====================

    /**
     * Get full display name for this contract.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->codice_contratto ?? ('Contratto #' . $this->id);
    }
}