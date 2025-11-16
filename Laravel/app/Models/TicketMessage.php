<?php
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
        'has_attachments', 
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'has_attachments' => 'boolean',
    ];

    protected $appends = ['user_name', 'user_role', 'role_letter'];

    /**
     * Get the ticket that owns the message.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who created the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_message_id');
    }

    /**
     * Get the username attribute.
     */
    public function getUserNameAttribute()
    {
        if ($this->user) {
            if ($this->user->name && $this->user->cognome) {
                return $this->user->name . ' ' . $this->user->cognome;
            } elseif (!empty($this->user->ragione_sociale)) {
                return $this->user->ragione_sociale;
            }
        }
        return 'Utente Sconosciuto';
    }

    /**
     * Get the user role attribute.
     */
    public function getUserRoleAttribute()
    {
        return $this->user && $this->user->role
            ? $this->user->role->descrizione
            : 'N/A';
    }

    /**
     * Get a single-letter code for the user's role (for avatars, etc.).
     */
    public function getRoleLetterAttribute()
    {
        // Se esiste l'utente e il suo role, prendo l'id del ruolo
        $roleId = $this->user && $this->user->role ? $this->user->role->id : null;

        switch ($roleId) {
            case 1: // Admin
                return 'A';
            case 2: // Backoffice
                return 'B';
            case 3: // Operatore web
                return 'O';
            case 4: // SEU
                return 'S';
            default:
                return 'U'; // Utente generico
        }
    }

    /**
     * Check if message has attachments.
     */
    public function hasAttachments()
    {
        return $this->has_attachments || $this->attachments()->exists();
    }

    /**
     * Get attachment count.
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
     * Get formatted total size of all attachments.
     */
    public function getFormattedTotalSizeAttribute()
    {
        $bytes = $this->total_attachment_size;
        
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Scopes
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

    public function scopeWithAttachments($query)
    {
        return $query->where('has_attachments', true);
    }

    public function scopeStatusChanges($query)
    {
        return $query->where('message_type', 'status_change');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Boot method to handle cascading deletes.
     */
    protected static function boot()
    {
        parent::boot();

        // When deleting a message, also delete its attachments
        static::deleting(function ($message) {
            $message->attachments()->each(function ($attachment) {
                $attachment->delete();
            });
        });
    }
}