<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class product extends Model
{
    use HasFactory;

    protected $fillable = [
        'descrizione',
        'supplier_id',
        'punti_carriera',
        'punti_valore',
        'attivo',
        'macro_product_id',
        'gettone',
        'inizio_offerta',
        'fine_offerta',
    ];

    protected $casts = [
        'punti_carriera' => 'integer',
        'punti_valore' => 'integer',
        'attivo' => 'boolean',
        'gettone' => 'decimal:2',
        'inizio_offerta' => 'date',
        'fine_offerta' => 'date',
    ];

    // Critical fields that affect business logic
    protected static $criticalFields = ['punti_valore', 'punti_carriera', 'attivo', 'gettone'];

    // ==================== RELATIONSHIPS ====================

    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    public function supplier()
    {
        return $this->belongsTo(supplier::class);
    }

    public function macro_product()
    {
        return $this->belongsTo(macro_product::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log product creation
        static::created(function ($product) {
            $product->load(['supplier', 'macro_product']);

            $supplierName = $product->supplier 
                ? $product->supplier->nome 
                : null;

            $macroProductName = $product->macro_product 
                ? $product->macro_product->descrizione 
                : null;

            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Product created", [
                'product_id' => $product->id,
                'descrizione' => $product->descrizione,
                'supplier_id' => $product->supplier_id,
                'supplier_name' => $supplierName,
                'macro_product_id' => $product->macro_product_id,
                'macro_product_name' => $macroProductName,
                'punti_valore' => $product->punti_valore,
                'punti_carriera' => $product->punti_carriera,
                'gettone' => $product->gettone,
                'attivo' => $product->attivo,
                'inizio_offerta' => $product->inizio_offerta?->format('d/m/Y'),
                'fine_offerta' => $product->fine_offerta?->format('d/m/Y'),
                'created_by' => $userName,
            ]);
        });

        // Log product updates
        static::updated(function ($product) {
            $changes = $product->getChanges();
            $original = $product->getOriginal();

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

                $level = $hasCriticalChanges ? 'warning' : 'info';

                SystemLogService::database()->{$level}("Product updated", [
                    'product_id' => $product->id,
                    'descrizione' => $product->descrizione,
                    'changes' => $changesForLog,
                    'critical_change' => $hasCriticalChanges,
                    'affected_contracts_count' => $product->contract()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log product deletion
        static::deleted(function ($product) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Product deleted", [
                'product_id' => $product->id,
                'descrizione' => $product->descrizione,
                'punti_valore' => $product->punti_valore,
                'punti_carriera' => $product->punti_carriera,
                'contracts_count' => $product->contract()->count(),
                'deleted_by' => $userName,
            ]);
        });
    }
}