<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class specific_data extends Model
{
    use HasFactory;

    protected $fillable = [
        'domanda',
        'risposta_tipo_numero',
        'risposta_tipo_stringa',
        'risposta_tipo_bool',
        'tipo_risposta',
        'contract_id',
    ];

    protected $casts = [
        'risposta_tipo_numero' => 'integer',
        'risposta_tipo_bool' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function contract()
    {
        return $this->belongsTo(contract::class);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get the response value based on type.
     */
    public function getRisposta(): mixed
    {
        return match($this->tipo_risposta) {
            'number' => $this->risposta_tipo_numero,
            'string', 'select' => $this->risposta_tipo_stringa,
            'boolean' => $this->risposta_tipo_bool,
            default => null,
        };
    }

    /**
     * Get formatted response for display.
     */
    public function getFormattedRisposta(): string
    {
        return match($this->tipo_risposta) {
            'number' => (string) $this->risposta_tipo_numero,
            'string', 'select' => $this->risposta_tipo_stringa ?? '',
            'boolean' => $this->risposta_tipo_bool ? 'Sì' : 'No',
            default => '',
        };
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log specific data creation with entity tracking
        static::created(function ($specificData) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            // Build logger with entity tracking
            $logger = SystemLogService::userActivity()
                ->forEntity('specific_data', $specificData->id);

            // Add contract tracking if available
            if ($specificData->contract_id) {
                $logger->forContract($specificData->contract_id);
            }

            $logger->info("Contract specific data created", [
                'specific_data_id' => $specificData->id,
                'contract_id' => $specificData->contract_id,
                'domanda' => $specificData->domanda,
                'tipo_risposta' => $specificData->tipo_risposta,
                'risposta' => $specificData->getFormattedRisposta(),
                'created_by' => $userName,
            ]);
        });

        // Log specific data updates with change tracking
        static::updated(function ($specificData) {
            $changes = $specificData->getChanges();
            $original = $specificData->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    // Format old and new values for response fields
                    $oldValue = $original[$field] ?? null;
                    
                    // Special formatting for boolean responses
                    if ($field === 'risposta_tipo_bool') {
                        $oldValue = $oldValue ? 'Sì' : 'No';
                        $newValue = $newValue ? 'Sì' : 'No';
                    }

                    $changesForLog[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // Build logger with entity tracking
                $logger = SystemLogService::userActivity()
                    ->forEntity('specific_data', $specificData->id);

                // Add contract tracking if available
                if ($specificData->contract_id) {
                    $logger->forContract($specificData->contract_id);
                }

                $logger->info("Contract specific data updated", [
                    'specific_data_id' => $specificData->id,
                    'contract_id' => $specificData->contract_id,
                    'domanda' => $specificData->domanda,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log specific data deletion
        static::deleted(function ($specificData) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            // Build logger with entity tracking
            $logger = SystemLogService::userActivity()
                ->forEntity('specific_data', $specificData->id);

            // Add contract tracking if available
            if ($specificData->contract_id) {
                $logger->forContract($specificData->contract_id);
            }

            $logger->warning("Contract specific data deleted", [
                'specific_data_id' => $specificData->id,
                'contract_id' => $specificData->contract_id,
                'domanda' => $specificData->domanda,
                'last_risposta' => $specificData->getFormattedRisposta(),
                'deleted_by' => $userName,
            ]);
        });
    }
}