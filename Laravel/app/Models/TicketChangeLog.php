<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketChangeLog extends Model
{
    use HasFactory;

    protected $table = 'ticket_changes_log';

    /**
     * Status constants
     */
    public const STATUS_NEW = 'new';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_REMOVED = 'removed';  // Permanent deletion from DB

    /**
     * Change type constants
     */
    public const CHANGE_TYPE_STATUS = 'status';
    public const CHANGE_TYPE_PRIORITY = 'priority';
    public const CHANGE_TYPE_BOTH = 'both';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'previous_status',
        'new_status',
        'previous_priority',
        'new_priority',
        'change_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeStatusChanges($query)
    {
        return $query->whereIn('change_type', [self::CHANGE_TYPE_STATUS, self::CHANGE_TYPE_BOTH]);
    }

    public function scopePriorityChanges($query)
    {
        return $query->whereIn('change_type', [self::CHANGE_TYPE_PRIORITY, self::CHANGE_TYPE_BOTH]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for auto-system changes (user_id = null)
     */
    public function scopeAutoChanges($query)
    {
        return $query->whereNull('user_id');
    }
}