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
        return $this->belongsTo(Contract::class);
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
            if ($this->assignedTo->nome && $this->assignedTo->cognome) {
                return $this->assignedTo->nome . ' ' . $this->assignedTo->cognome;
            } elseif (!empty($this->assignedTo->rag_soc)) {
                return $this->assignedTo->rag_soc;
            }
            return $this->assignedTo->email;
        }
        return 'Non assegnato';
    }

    public function getCreatedByNameAttribute()
    {
        if ($this->createdBy) {
            if ($this->createdBy->nome && $this->createdBy->cognome) {
                return $this->createdBy->nome . ' ' . $this->createdBy->cognome;
            } elseif (!empty($this->createdBy->rag_soc)) {
                return $this->createdBy->rag_soc;
            }
            return $this->createdBy->email;
        }
        return 'N/A';
    }

    public static function generateTicketNumber()
    {
        $lastTicket = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastTicket ? $lastTicket->id + 1 : 1;
        return 'TK-' + str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
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

    // Boot method to auto-generate ticket number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }
}