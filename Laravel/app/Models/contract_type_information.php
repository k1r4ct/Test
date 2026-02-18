<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use App\Traits\LogsDatabaseOperations;

class contract_type_information extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'macro_product_id',
        'domanda',
        'tipo_risposta',
        'obbligatorio',
    ];

    protected $casts = [
        'obbligatorio' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function macro_product()
    {
        return $this->belongsTo(macro_product::class);
    }

    public function DetailQuestion()
    {
        return $this->hasMany(DetailQuestion::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log question creation
        static::created(function ($typeInfo) {
            SystemLogService::database()->info("Contract type information created", [
                'contract_type_info_id' => $typeInfo->id,
                'macro_product_id' => $typeInfo->macro_product_id,
                'domanda' => $typeInfo->domanda,
                'tipo_risposta' => $typeInfo->tipo_risposta,
                'obbligatorio' => $typeInfo->obbligatorio,
            ]);
        });

        // Log question updates
        static::updated(function ($typeInfo) {
            $changes = $typeInfo->getChanges();
            $original = $typeInfo->getOriginal();

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
                SystemLogService::database()->info("Contract type information updated", [
                    'contract_type_info_id' => $typeInfo->id,
                    'macro_product_id' => $typeInfo->macro_product_id,
                    'domanda' => $typeInfo->domanda,
                    'changes' => $changesForLog,
                ]);
            }
        });

        // Log question deletion
        static::deleted(function ($typeInfo) {
            SystemLogService::database()->warning("Contract type information deleted", [
                'contract_type_info_id' => $typeInfo->id,
                'macro_product_id' => $typeInfo->macro_product_id,
                'domanda' => $typeInfo->domanda,
            ]);
        });
    }
}