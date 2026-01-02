<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;

class notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'reparto',
        'notifica',
        'visualizzato',
        'notifica_html',
    ];

    protected $casts = [
        'visualizzato' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function User()
    {
        return $this->hasMany(User::class, 'to_user_id');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log notification creation
        static::created(function ($notification) {
            $notification->load(['fromUser', 'toUser']);

            $fromName = $notification->fromUser 
                ? $notification->fromUser->name . ' ' . $notification->fromUser->cognome 
                : 'Sistema';

            $toName = $notification->toUser 
                ? $notification->toUser->name . ' ' . $notification->toUser->cognome 
                : 'User #' . $notification->to_user_id;

            SystemLogService::database()->info("Notification created", [
                'notification_id' => $notification->id,
                'from_user_id' => $notification->from_user_id,
                'from_name' => $fromName,
                'to_user_id' => $notification->to_user_id,
                'to_name' => $toName,
                'reparto' => $notification->reparto,
                'notification_preview' => \Str::limit(strip_tags($notification->notifica), 100),
            ]);
        });

        // Log notification read status change
        static::updated(function ($notification) {
            if ($notification->isDirty('visualizzato') && $notification->visualizzato) {
                SystemLogService::database()->info("Notification read", [
                    'notification_id' => $notification->id,
                    'to_user_id' => $notification->to_user_id,
                ]);
            }
        });

        // Log notification deletion
        static::deleted(function ($notification) {
            SystemLogService::database()->info("Notification deleted", [
                'notification_id' => $notification->id,
                'to_user_id' => $notification->to_user_id,
                'was_read' => $notification->visualizzato,
            ]);
        });
    }
}