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
        'type',
        'entity_type',
        'entity_id',
    ];

    protected $casts = [
        'visualizzato' => 'boolean',
    ];

    // ==================== NOTIFICATION TYPE CONSTANTS ====================

    // Ticket notification types
    public const TYPE_TICKET_NEW = 'ticket_new';
    public const TYPE_TICKET_ASSIGNED = 'ticket_assigned';
    public const TYPE_TICKET_MESSAGE = 'ticket_message';
    public const TYPE_TICKET_WAITING = 'ticket_waiting';
    public const TYPE_TICKET_RESOLVED = 'ticket_resolved';
    public const TYPE_TICKET_CLOSED = 'ticket_closed';

    // Contract notification types
    public const TYPE_CONTRACT_STATUS = 'contract_status';

    // Entity types
    public const ENTITY_TICKET = 'ticket';
    public const ENTITY_CONTRACT = 'contract';

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

    /**
     * Get the related ticket (if entity_type is ticket)
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'entity_id');
    }

    /**
     * Get the related contract (if entity_type is contract)
     */
    public function contract()
    {
        return $this->belongsTo(contract::class, 'entity_id');
    }

    /**
     * Get the related entity dynamically based on entity_type
     */
    public function getEntityAttribute()
    {
        if ($this->entity_type === self::ENTITY_TICKET) {
            return $this->ticket;
        }
        
        if ($this->entity_type === self::ENTITY_CONTRACT) {
            return $this->contract;
        }
        
        return null;
    }

    // ==================== SCOPES ====================

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('visualizzato', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('visualizzato', true);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    /**
     * Scope for ticket notifications
     */
    public function scopeTicketNotifications($query)
    {
        return $query->where('entity_type', self::ENTITY_TICKET);
    }

    /**
     * Scope for contract notifications
     */
    public function scopeContractNotifications($query)
    {
        return $query->where('entity_type', self::ENTITY_CONTRACT);
    }

    /**
     * Scope for specific notification type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for recent notifications (last N days)
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== HELPER METHODS ====================

    /**
     * Mark notification as read
     */
    public function markAsRead(): bool
    {
        return $this->update(['visualizzato' => true]);
    }

    /**
     * Check if notification is for a ticket
     */
    public function isTicketNotification(): bool
    {
        return $this->entity_type === self::ENTITY_TICKET;
    }

    /**
     * Check if notification is for a contract
     */
    public function isContractNotification(): bool
    {
        return $this->entity_type === self::ENTITY_CONTRACT;
    }

    /**
     * Get human-readable notification type label
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_TICKET_NEW => 'Nuovo Ticket',
            self::TYPE_TICKET_ASSIGNED => 'Ticket Assegnato',
            self::TYPE_TICKET_MESSAGE => 'Nuovo Messaggio',
            self::TYPE_TICKET_WAITING => 'Ticket in Lavorazione',
            self::TYPE_TICKET_RESOLVED => 'Ticket Risolto',
            self::TYPE_TICKET_CLOSED => 'Ticket Chiuso',
            self::TYPE_CONTRACT_STATUS => 'Cambio Stato Contratto',
        ];

        return $labels[$this->type] ?? 'Notifica';
    }

    /**
     * Get icon name for notification type (Material Icons)
     */
    public function getIconAttribute(): string
    {
        $icons = [
            self::TYPE_TICKET_NEW => 'confirmation_number',
            self::TYPE_TICKET_ASSIGNED => 'assignment_ind',
            self::TYPE_TICKET_MESSAGE => 'chat',
            self::TYPE_TICKET_WAITING => 'hourglass_empty',
            self::TYPE_TICKET_RESOLVED => 'check_circle',
            self::TYPE_TICKET_CLOSED => 'archive',
            self::TYPE_CONTRACT_STATUS => 'description',
        ];

        return $icons[$this->type] ?? 'notifications';
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
                'type' => $notification->type,
                'entity_type' => $notification->entity_type,
                'entity_id' => $notification->entity_id,
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