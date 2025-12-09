<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'status',
        'previous_status',
        'priority',
        'contract_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'resolved_at',
        'closed_at',
        'restored_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'restored_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants for better code readability
     */
    public const STATUS_NEW = 'new';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_DELETED = 'deleted';

    /**
     * Priority constants
     */
    public const PRIORITY_UNASSIGNED = 'unassigned';
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    // Relationships
    public function contract()
    {
        return $this->belongsTo(contract::class,'contract_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function changeLogs()
    {
        return $this->hasMany(TicketChangeLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all attachments for the ticket.
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get attachments count.
     */
    public function getAttachmentCountAttribute()
    {
        return $this->attachments()->count();
    }

    /**
     * Get total size of all attachments.
     */
    public function getTotalAttachmentSizeAttribute()
    {
        return $this->attachments()->sum('file_size');
    }

    /**
     * Get formatted total size.
     */
    public function getFormattedTotalAttachmentSizeAttribute()
    {
        $bytes = $this->total_attachment_size;
        
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if ticket has attachments.
     */
    public function hasAttachments()
    {
        return $this->attachments()->exists();
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Scope to get tickets with attachments.
     */
    public function scopeWithAttachments($query)
    {
        return $query->has('attachments');
    }

    /**
     * Scope for open tickets (new or waiting)
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_WAITING]);
    }

    /**
     * Scope for resolved tickets
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope for archived tickets (closed)
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope for tickets in deleted status
     */
    public function scopeInDeletedStatus($query)
    {
        return $query->where('status', self::STATUS_DELETED);
    }

    /**
     * Scope for tickets eligible for priority escalation
     * Only tickets in 'new' or 'waiting' with 'unassigned' priority
     */
    public function scopeEligibleForPriorityEscalation($query)
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_WAITING])
                     ->where('priority', self::PRIORITY_UNASSIGNED);
    }

    /**
     * Scope for tickets eligible for auto-close (resolved > X days)
     */
    public function scopeEligibleForAutoClose($query, int $days = 10)
    {
        return $query->where('status', self::STATUS_RESOLVED)
                     ->where('resolved_at', '<=', now()->subDays($days));
    }

    /**
     * Scope for tickets eligible for auto-delete (closed > X days)
     */
    public function scopeEligibleForAutoDelete($query, int $days = 10)
    {
        return $query->where('status', self::STATUS_CLOSED)
                     ->where('closed_at', '<=', now()->subDays($days));
    }

    /**
     * Scope for tickets eligible for permanent removal (deleted > X days)
     */
    public function scopeEligibleForPermanentRemoval($query, int $days = 40)
    {
        return $query->where('status', self::STATUS_DELETED)
                     ->where('deleted_at', '<=', now()->subDays($days));
    }

    // Attributes
    public function getCustomerNameAttribute()
    {
        if ($this->contract && $this->contract->customer_data) {
            $customer = $this->contract->customer_data;
            if ($customer->nome && $customer->cognome) {
                return $customer->nome . ' ' . $customer->cognome;
            } elseif (!empty($customer->ragione_sociale)) {
                return $customer->ragione_sociale;
            }
        }
        return 'N/A';
    }

    public function getProductNameAttribute()
    {
        return $this->contract && $this->contract->product
            ? $this->contract->product->descrizione
            : 'N/A';
    }

    public function getAssignedToNameAttribute()
    {
        if ($this->assignedTo) {
            if ($this->assignedTo->name && $this->assignedTo->cognome) {
                return $this->assignedTo->name . ' ' . $this->assignedTo->cognome;
            } elseif (!empty($this->assignedTo->ragione_sociale)) {
                return $this->assignedTo->ragione_sociale;
            }
            return $this->assignedTo->email;
        }
        return 'Non assegnato';
    }

    public function getCreatedByNameAttribute()
    {
        if ($this->createdBy) {
            if ($this->createdBy->name && $this->createdBy->cognome) {
                return $this->createdBy->name . ' ' . $this->createdBy->cognome;
            } elseif (!empty($this->createdBy->ragione_sociale)) {
                return $this->createdBy->ragione_sociale;
            }
            return $this->createdBy->email;
        }
        return 'N/A';
    }

    /**
     * Get the number of days since the ticket was created
     */
    public function getDaysOpenAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get the number of days since the ticket was last updated
     * Used for priority escalation
     */
    public function getDaysSinceUpdateAttribute(): int
    {
        return $this->updated_at->diffInDays(now());
    }

    /**
     * Get the number of days since the ticket was resolved
     */
    public function getDaysResolvedAttribute(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }
        return $this->resolved_at->diffInDays(now());
    }

    /**
     * Get the number of days since the ticket was archived (closed)
     */
    public function getDaysArchivedAttribute(): ?int
    {
        if (!$this->closed_at) {
            return null;
        }
        return $this->closed_at->diffInDays(now());
    }

    /**
     * Get the number of days since the ticket was soft-deleted
     */
    public function getDaysDeletedAttribute(): ?int
    {
        if (!$this->deleted_at) {
            return null;
        }
        return $this->deleted_at->diffInDays(now());
    }

    /**
     * Check if ticket is open
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_WAITING]);
    }

    /**
     * Check if ticket is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if ticket is archived
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Check if ticket is in deleted status
     */
    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Check if priority was manually assigned
     * Returns true if priority is anything other than 'unassigned'
     */
    public function hasPriorityManuallyAssigned(): bool
    {
        return $this->priority !== self::PRIORITY_UNASSIGNED;
    }

    /**
     * Check if ticket is eligible for priority escalation
     */
    public function isEligibleForPriorityEscalation(): bool
    {
        return $this->isOpen() && !$this->hasPriorityManuallyAssigned();
    }

    public static function generateTicketNumber()
    {
        $lastTicket = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastTicket ? $lastTicket->id + 1 : 1;
        return 'TK-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public static function getStatusOptions()
    {
        return [
            'new'      => 'Nuovo',
            'waiting'  => 'In Lavorazione',
            'resolved' => 'Risolto',
            'closed'   => 'Chiuso',
            'deleted'  => 'Cancellato',
        ];
    }

    public static function getPriorityOptions()
    {
        return [
            'high'       => 'Alta',
            'medium'     => 'Media',
            'low'        => 'Bassa',
            'unassigned' => 'Non Assegnata',
        ];
    }

    // Boot method to auto-generate ticket number and handle cascading deletes
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });

        // When deleting a ticket, also delete its attachments
        static::deleting(function ($ticket) {
            $ticket->attachments()->each(function ($attachment) {
                $attachment->delete();
            });
        });
    }
}