<?php
// File: Laravel/app/Models/TicketMessage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'message_type',
        'attachment_path',
        'attachment_name',
        'attachment_size',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUserNameAttribute()
    {
        if ($this->user) {
            if ($this->user->nome && $this->user->cognome) {
                return $this->user->nome . ' ' . $this->user->cognome;
            } elseif ($this->user->rag_soc) {
                return $this->user->rag_soc;
            }
        }
        return 'Utente Sconosciuto';
    }

    public function getUserRoleAttribute()
    {
        return $this->user && $this->user->role ? 
               $this->user->role->descrizione : 'N/A';
    }

    public function getFormattedSizeAttribute()
    {
        if (!$this->attachment_size) {
            return null;
        }

        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function scopeByTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeTextMessages($query)
    {
        return $query->where('message_type', 'text');
    }

    public function scopeAttachments($query)
    {
        return $query->where('message_type', 'attachment');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}