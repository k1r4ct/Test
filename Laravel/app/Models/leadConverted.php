<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

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
        // Log lead conversion (CRITICAL business event)
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

            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::userActivity()->info("Lead converted to client", [
                'conversion_id' => $conversion->id,
                'lead_id' => $conversion->lead_id,
                'lead_name' => $leadName,
                'cliente_id' => $conversion->cliente_id,
                'client_name' => $clientName,
                'inviter' => $inviterInfo,
                'converted_by' => $userName,
            ]);
        });

        // Log conversion deletion (revert)
        static::deleted(function ($conversion) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::userActivity()->warning("Lead conversion reverted", [
                'conversion_id' => $conversion->id,
                'lead_id' => $conversion->lead_id,
                'cliente_id' => $conversion->cliente_id,
                'reverted_by' => $userName,
            ]);
        });
    }
}