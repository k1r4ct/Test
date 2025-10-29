<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class contract extends Model
{
    use HasFactory;

    const BONUS_COEFFICIENT = 0.5; // 50% of contract PV goes to inviter as bonus

    protected $fillable=[
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

    public function User(){
        return $this->belongsTo(User::class,'associato_a_user_id');
    }

    public function UserSeu(){
        return $this->belongsTo(User::class,'inserito_da_user_id');
    }

    public function customer_data(){
        return $this->belongsTo(customer_data::class);
    }

    public function status_contract(){
        return $this->belongsTo(status_contract::class);
    }

    public function product(){
        return $this->belongsTo(product::class);
    }

    public function specific_data(){
        return $this->hasMany(specific_data::class);
    }

    public function payment_mode(){
        return $this->belongsTo(payment_mode::class);
    }

    public function document_data(){
        return $this->belongsTo(document_data::class);
    }

    public function backofficeNote(){
        return $this->hasMany(backoffice_note::class);
    }

    // ==================== EVENTS ====================

    /**
     * Automatic PV/PC assignment and Bonus calculation on status change
     */
    protected static function booted()
    {
        static::updated(function ($contract) {
            // Only proceed if status changed
            if (!$contract->isDirty('status_contract_id')) {
                return;
            }

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
                    Log::warning("Contract {$contract->id}: No macro product found");
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
                Log::error("Error processing contract {$contract->id} status change: " . $e->getMessage());
                throw $e;
            }
        });
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

        Log::info("Points assigned to user", [
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

        Log::warning("Points revoked from user", [
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
            Log::info("Contract {$contract->id}: Client not from lead. No bonus.");
            return null;
        }

        // Get the original lead
        $lead = lead::find($leadConverted->lead_id);
        
        if (!$lead || !$lead->invitato_da_user_id) {
            Log::info("Contract {$contract->id}: Lead not invited by anyone. No bonus.");
            return null;
        }

        // Get the inviter user
        $inviter = User::find($lead->invitato_da_user_id);
        
        if (!$inviter) {
            Log::warning("Contract {$contract->id}: Inviter {$lead->invitato_da_user_id} not found.");
            return null;
        }

        // Calculate and assign bonus
        $bonusPoints = (int) round($puntiValore * self::BONUS_COEFFICIENT);
        $inviter->increment('punti_bonus', $bonusPoints);

        Log::info("Bonus assigned to inviter", [
            'contract_id' => $contract->id,
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

        Log::warning("Bonus revoked from inviter", [
            'contract_id' => $contract->id,
            'contract_pv' => $puntiValore,
            'bonus_revoked' => $bonusPoints,
            'inviter_id' => $inviter->id,
            'inviter_name' => $inviter->name . ' ' . $inviter->cognome,
            'inviter_new_bonus_balance' => $inviter->punti_bonus,
        ]);

        return $bonusPoints;
    }

    public function ticket()
    {
        return $this->hasMany(Ticket::class);
    }
}