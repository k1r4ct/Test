<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class macro_product extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'codice_macro',
        'descrizione',
        'punti_valore',
        'punti_carriera',
        'supplier_category_id',
    ];

    protected $casts = [
        'punti_valore' => 'integer',
        'punti_carriera' => 'integer',
    ];

    // Critical fields that affect point calculations
    protected static $criticalFields = ['punti_valore', 'punti_carriera'];

    // ==================== RELATIONSHIPS ====================

    public function supplier_category()
    {
        return $this->belongsTo(supplier_category::class);
    }

    public function product()
    {
        return $this->hasMany(product::class);
    }

    public function contract_type_information()
    {
        return $this->hasMany(contract_type_information::class);
    }

    public function contract_management()
    {
        return $this->hasMany(contract_management::class, "user_id");
    }

    public function contract_managementProduct()
    {
        return $this->hasMany(contract_management::class, "macro_product_id");
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log macro product creation
        static::created(function ($macroProduct) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Macro product created", [
                'macro_product_id' => $macroProduct->id,
                'codice_macro' => $macroProduct->codice_macro,
                'descrizione' => $macroProduct->descrizione,
                'punti_valore' => $macroProduct->punti_valore,
                'punti_carriera' => $macroProduct->punti_carriera,
                'supplier_category_id' => $macroProduct->supplier_category_id,
                'created_by' => $userName,
            ]);
        });

        // Log macro product updates (CRITICAL for PV/PC changes)
        static::updated(function ($macroProduct) {
            $changes = $macroProduct->getChanges();
            $original = $macroProduct->getOriginal();

            $changesForLog = [];
            $hasCriticalChanges = false;

            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];

                    if (in_array($field, static::$criticalFields)) {
                        $hasCriticalChanges = true;
                    }
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // Use warning for PV/PC changes as they affect calculations
                $level = $hasCriticalChanges ? 'warning' : 'info';

                SystemLogService::database()->{$level}("Macro product updated", [
                    'macro_product_id' => $macroProduct->id,
                    'codice_macro' => $macroProduct->codice_macro,
                    'descrizione' => $macroProduct->descrizione,
                    'changes' => $changesForLog,
                    'critical_change' => $hasCriticalChanges,
                    'affected_products_count' => $macroProduct->product()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log macro product deletion
        static::deleted(function ($macroProduct) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Macro product deleted", [
                'macro_product_id' => $macroProduct->id,
                'codice_macro' => $macroProduct->codice_macro,
                'descrizione' => $macroProduct->descrizione,
                'punti_valore' => $macroProduct->punti_valore,
                'punti_carriera' => $macroProduct->punti_carriera,
                'deleted_by' => $userName,
            ]);
        });
    }
}