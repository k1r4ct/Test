<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemLogService;

class leadConverted extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'cliente_id',
    ];

    // ==================== RELATIONSHIPS ====================

    public function User()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function Lead()
    {
        return $this->belongsTo(lead::class, 'lead_id');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log lead conversion (CRITICAL business event) with entity tracking
        static::created(function ($conversion) {
            $conversion->load(['Lead', 'User']);

            $leadName = $conversion->Lead 
                ? $conversion->Lead->nome . ' ' . $conversion->Lead->cognome 
                : 'Lead #' . $conversion->lead_id;

            $clientName = $conversion->User 
                ? $conversion->User->name . ' ' . $conversion->User->cognome 
                : 'Cliente #' . $conversion->cliente_id;

            // Get inviter info for bonus tracking
            $inviterInfo = null;
            if ($conversion->Lead && $conversion->Lead->invitato_da_user_id) {
                $inviter = User::find($conversion->Lead->invitato_da_user_id);
                if ($inviter) {
                    $inviterInfo = [
                        'id' => $inviter->id,
                        'name' => $inviter->name . ' ' . $inviter->cognome,
                    ];
                }
            }

            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            // Use forEntity for audit trail - track both lead and the conversion itself
            SystemLogService::userActivity()
                ->forEntity('lead_converted', $conversion->id)
                ->info("Lead converted to client", [
                    'conversion_id' => $conversion->id,
                    'lead_id' => $conversion->lead_id,
                    'lead_name' => $leadName,
                    'lead_email' => $conversion->Lead?->email,
                    'cliente_id' => $conversion->cliente_id,
                    'client_name' => $clientName,
                    'client_email' => $conversion->User?->email,
                    'inviter' => $inviterInfo,
                    'potential_bonus_recipient' => $inviterInfo ? $inviterInfo['name'] : null,
                    'converted_by' => $operatorName,
                ]);

            // Also log on the lead entity for cross-reference
            SystemLogService::userActivity()
                ->forEntity('lead', $conversion->lead_id)
                ->info("Lead converted to client", [
                    'conversion_id' => $conversion->id,
                    'lead_id' => $conversion->lead_id,
                    'lead_name' => $leadName,
                    'new_client_id' => $conversion->cliente_id,
                    'new_client_name' => $clientName,
                    'converted_by' => $operatorName,
                ]);
        });

        // Log conversion deletion (revert) with entity tracking
        static::deleted(function ($conversion) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            // Get names before relationships are lost
            $leadName = $conversion->Lead 
                ? $conversion->Lead->nome . ' ' . $conversion->Lead->cognome 
                : 'Lead #' . $conversion->lead_id;

            $clientName = $conversion->User 
                ? $conversion->User->name . ' ' . $conversion->User->cognome 
                : 'Cliente #' . $conversion->cliente_id;

            // Use forEntity for audit trail
            SystemLogService::userActivity()
                ->forEntity('lead_converted', $conversion->id)
                ->warning("Lead conversion reverted", [
                    'conversion_id' => $conversion->id,
                    'lead_id' => $conversion->lead_id,
                    'lead_name' => $leadName,
                    'cliente_id' => $conversion->cliente_id,
                    'client_name' => $clientName,
                    'reverted_by' => $operatorName,
                ]);

            // Also log on the lead entity for cross-reference
            SystemLogService::userActivity()
                ->forEntity('lead', $conversion->lead_id)
                ->warning("Lead conversion reverted", [
                    'conversion_id' => $conversion->id,
                    'lead_id' => $conversion->lead_id,
                    'former_client_id' => $conversion->cliente_id,
                    'reverted_by' => $operatorName,
                ]);
        });
    }
}