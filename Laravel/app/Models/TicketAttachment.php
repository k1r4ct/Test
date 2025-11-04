<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ticket_id',
        'ticket_message_id',
        'user_id',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'hash'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the ticket that owns the attachment.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the message that owns the attachment.
     */
    public function message()
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full storage path.
     */
    public function getFullPathAttribute()
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Get human readable file size.
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the username of uploader.
     */
    public function getUserNameAttribute()
    {
        if ($this->user) {
            if ($this->user->name && $this->user->cognome) {
                return $this->user->name . ' ' . $this->user->cognome;
            } elseif (!empty($this->user->ragione_sociale)) {
                return $this->user->ragione_sociale;
            }
            return $this->user->email;
        }
        return 'Utente Sconosciuto';
    }

    /**
     * Check if file exists on disk.
     */
    public function fileExists()
    {
        return file_exists($this->full_path);
    }

    /**
     * Delete the physical file when model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if ($attachment->fileExists()) {
                unlink($attachment->full_path);
            }
        });
    }

    /**
     * Get the download URL for the attachment.
     */
    public function getDownloadUrlAttribute()
    {
        return route('tickets.attachment.download', ['attachment' => $this->id]);
    }

    /**
     * Check if file is an image.
     */
    public function getIsImageAttribute()
    {
        return in_array(strtolower($this->file_extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    /**
     * Check if file is a PDF.
     */
    public function getIsPdfAttribute()
    {
        return strtolower($this->file_extension) === 'pdf';
    }

    /**
     * Check if file is a document.
     */
    public function getIsDocumentAttribute()
    {
        return in_array(strtolower($this->file_extension), ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp']);
    }
}