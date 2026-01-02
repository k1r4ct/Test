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

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log specific data creation
        static::created(function ($specificData) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            // Get the response value based on type
            $risposta = null;
            switch ($specificData->tipo_risposta) {
                case 'number':
                    $risposta = $specificData->risposta_tipo_numero;
                    break;
                case 'string':
                case 'select':
                    $risposta = $specificData->risposta_tipo_stringa;
                    break;
                case 'boolean':
                    $risposta = $specificData->risposta_tipo_bool ? 'Sì' : 'No';
                    break;
            }

            SystemLogService::database()->info("Contract specific data created", [
                'specific_data_id' => $specificData->id,
                'contract_id' => $specificData->contract_id,
                'domanda' => $specificData->domanda,
                'tipo_risposta' => $specificData->tipo_risposta,
                'risposta' => $risposta,
                'created_by' => $userName,
            ]);
        });

        // Log specific data updates
        static::updated(function ($specificData) {
            $changes = $specificData->getChanges();
            $original = $specificData->getOriginal();

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

                SystemLogService::database()->info("Contract specific data updated", [
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

            SystemLogService::database()->info("Contract specific data deleted", [
                'specific_data_id' => $specificData->id,
                'contract_id' => $specificData->contract_id,
                'domanda' => $specificData->domanda,
                'deleted_by' => $userName,
            ]);
        });
    }
}