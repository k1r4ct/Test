<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemLogService;

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
        // Log note creation with contract tracking
        static::created(function ($note) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';
            
            // Build logger with entity and contract tracking
            $logger = SystemLogService::userActivity()
                ->forEntity('backoffice_note', $note->id);

            // Link to contract for filtering
            if ($note->contract_id) {
                $logger->forContract($note->contract_id);
            }

            // Get contract code for context
            $contractCode = $note->contract?->codice_contratto;

            $logger->info("Backoffice note created", [
                'note_id' => $note->id,
                'contract_id' => $note->contract_id,
                'contract_code' => $contractCode,
                'created_by' => $operatorName,
                'note_preview' => static::truncateNote($note->nota),
            ]);
        });

        // Log note updates with contract tracking
        static::updated(function ($note) {
            $changes = $note->getChanges();
            $original = $note->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $oldValue = $original[$field] ?? null;
                    
                    // Truncate nota field in changes
                    if ($field === 'nota') {
                        $oldValue = static::truncateNote($oldValue);
                        $newValue = static::truncateNote($newValue);
                    }
                    
                    $changesForLog[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                $operatorName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';
                
                // Build logger with entity and contract tracking
                $logger = SystemLogService::userActivity()
                    ->forEntity('backoffice_note', $note->id);

                // Link to contract for filtering
                if ($note->contract_id) {
                    $logger->forContract($note->contract_id);
                }

                // Get contract code for context
                $contractCode = $note->contract?->codice_contratto;

                $logger->info("Backoffice note updated", [
                    'note_id' => $note->id,
                    'contract_id' => $note->contract_id,
                    'contract_code' => $contractCode,
                    'changes' => $changesForLog,
                    'updated_by' => $operatorName,
                ]);
            }
        });

        // Log note deletion with contract tracking
        static::deleted(function ($note) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';
            
            // Build logger with entity and contract tracking
            $logger = SystemLogService::userActivity()
                ->forEntity('backoffice_note', $note->id);

            // Link to contract for filtering
            if ($note->contract_id) {
                $logger->forContract($note->contract_id);
            }

            // Get contract code for context
            $contractCode = $note->contract?->codice_contratto;

            $logger->warning("Backoffice note deleted", [
                'note_id' => $note->id,
                'contract_id' => $note->contract_id,
                'contract_code' => $contractCode,
                'deleted_by' => $operatorName,
                'note_preview' => static::truncateNote($note->nota),
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