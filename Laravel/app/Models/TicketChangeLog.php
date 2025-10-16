<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketChangeLog extends Model
{
    use HasFactory;

    protected $table = 'ticket_changes_log';

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
        return $query->whereIn('change_type', ['status', 'both']);
    }

    public function scopePriorityChanges($query)
    {
        return $query->whereIn('change_type', ['priority', 'both']);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}