<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class backoffice_note extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'nota',
    ];

    // ==================== RELATIONSHIPS ====================

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log note creation
        static::created(function ($note) {
            $userName = Auth::check() ? Auth::user()->name . ' ' . Auth::user()->cognome : 'Sistema';
            
            SystemLogService::database()->info("Backoffice note created", [
                'note_id' => $note->id,
                'contract_id' => $note->contract_id,
                'created_by' => $userName,
                'note_preview' => static::truncateNote($note->nota),
            ]);
        });

        // Log note updates
        static::updated(function ($note) {
            $userName = Auth::check() ? Auth::user()->name . ' ' . Auth::user()->cognome : 'Sistema';
            
            SystemLogService::database()->info("Backoffice note updated", [
                'note_id' => $note->id,
                'contract_id' => $note->contract_id,
                'updated_by' => $userName,
                'note_preview' => static::truncateNote($note->nota),
            ]);
        });

        // Log note deletion
        static::deleted(function ($note) {
            $userName = Auth::check() ? Auth::user()->name . ' ' . Auth::user()->cognome : 'Sistema';
            
            SystemLogService::database()->warning("Backoffice note deleted", [
                'note_id' => $note->id,
                'contract_id' => $note->contract_id,
                'deleted_by' => $userName,
            ]);
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Truncate note for logging (first 100 chars)
     */
    protected static function truncateNote(?string $nota): ?string
    {
        if (empty($nota)) {
            return null;
        }

        if (strlen($nota) <= 100) {
            return $nota;
        }

        return substr($nota, 0, 100) . '...';
    }
}