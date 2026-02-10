<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class supplier_category extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'nome_categoria',
        'descrizione',
    ];

    // ==================== RELATIONSHIPS ====================

    public function supplier()
    {
        return $this->hasMany(supplier::class);
    }

    public function macro_product()
    {
        return $this->hasMany(macro_product::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log category creation
        static::created(function ($category) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Supplier category created", [
                'supplier_category_id' => $category->id,
                'nome_categoria' => $category->nome_categoria,
                'descrizione' => $category->descrizione,
                'created_by' => $userName,
            ]);
        });

        // Log category updates
        static::updated(function ($category) {
            $changes = $category->getChanges();
            $original = $category->getOriginal();

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

                SystemLogService::database()->info("Supplier category updated", [
                    'supplier_category_id' => $category->id,
                    'nome_categoria' => $category->nome_categoria,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log category deletion
        static::deleted(function ($category) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Supplier category deleted", [
                'supplier_category_id' => $category->id,
                'nome_categoria' => $category->nome_categoria,
                'suppliers_count' => $category->supplier()->count(),
                'macro_products_count' => $category->macro_product()->count(),
                'deleted_by' => $userName,
            ]);
        });
    }
}