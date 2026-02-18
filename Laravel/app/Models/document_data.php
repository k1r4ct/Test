<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class document_data extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'tipo',
        'descrizione',
        'path_storage',
    ];

    // ==================== RELATIONSHIPS ====================

    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log document creation
        static::created(function ($document) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Document created", [
                'document_id' => $document->id,
                'tipo' => $document->tipo,
                'descrizione' => $document->descrizione,
                'path_storage' => $document->path_storage,
                'created_by' => $userName,
            ]);
        });

        // Log document updates
        static::updated(function ($document) {
            $changes = $document->getChanges();
            $original = $document->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                SystemLogService::database()->info("Document updated", [
                    'document_id' => $document->id,
                    'tipo' => $document->tipo,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log document deletion
        static::deleted(function ($document) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Document deleted", [
                'document_id' => $document->id,
                'tipo' => $document->tipo,
                'descrizione' => $document->descrizione,
                'path_storage' => $document->path_storage,
                'deleted_by' => $userName,
            ]);
        });
    }
}