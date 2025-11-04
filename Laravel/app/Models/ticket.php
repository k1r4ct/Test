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
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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