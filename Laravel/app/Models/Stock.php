<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class Stock extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $table = 'stock';

    protected $fillable = [
        'article_id',
        'store_id',
        'quantity',
        'total_stock',
        'minimum_stock',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'total_stock' => 'integer',
        'minimum_stock' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // ==================== SCOPES ====================

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'minimum_stock');
    }

    public function scopeByArticle($query, $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    // ==================== HELPER METHODS ====================

    public function isLowStock()
    {
        return $this->quantity <= $this->minimum_stock;
    }

    public function isOutOfStock()
    {
        return $this->quantity <= 0;
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log stock creation
        static::created(function ($stock) {
            $stock->load(['article', 'store']);

            $articleName = $stock->article 
                ? $stock->article->article_name 
                : 'Article #' . $stock->article_id;

            $storeName = $stock->store 
                ? $stock->store->store_name 
                : 'Store #' . $stock->store_id;

            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Stock record created", [
                'stock_id' => $stock->id,
                'article_id' => $stock->article_id,
                'article_name' => $articleName,
                'store_id' => $stock->store_id,
                'store_name' => $storeName,
                'quantity' => $stock->quantity,
                'total_stock' => $stock->total_stock,
                'minimum_stock' => $stock->minimum_stock,
                'created_by' => $userName,
            ]);
        });

        // Log stock updates (quantity changes are critical!)
        static::updated(function ($stock) {
            $changes = $stock->getChanges();
            $original = $stock->getOriginal();

            $changesForLog = [];
            $hasQuantityChange = false;

            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];

                    if ($field === 'quantity') {
                        $hasQuantityChange = true;
                    }
                }
            }

            if (!empty($changesForLog)) {
                $stock->load(['article', 'store']);

                $articleName = $stock->article 
                    ? $stock->article->article_name 
                    : 'Article #' . $stock->article_id;

                $storeName = $stock->store 
                    ? $stock->store->store_name 
                    : 'Store #' . $stock->store_id;

                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // Check for low stock or out of stock
                $stockWarning = null;
                if ($stock->isOutOfStock()) {
                    $stockWarning = 'OUT_OF_STOCK';
                } elseif ($stock->isLowStock()) {
                    $stockWarning = 'LOW_STOCK';
                }

                // Use warning for quantity changes or stock alerts
                $level = ($hasQuantityChange && $stockWarning) ? 'warning' : 'info';

                SystemLogService::ecommerce()->{$level}("Stock updated", [
                    'stock_id' => $stock->id,
                    'article_id' => $stock->article_id,
                    'article_name' => $articleName,
                    'store_id' => $stock->store_id,
                    'store_name' => $storeName,
                    'changes' => $changesForLog,
                    'current_quantity' => $stock->quantity,
                    'minimum_stock' => $stock->minimum_stock,
                    'stock_warning' => $stockWarning,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log stock deletion
        static::deleted(function ($stock) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Stock record deleted", [
                'stock_id' => $stock->id,
                'article_id' => $stock->article_id,
                'store_id' => $stock->store_id,
                'quantity_at_deletion' => $stock->quantity,
                'deleted_by' => $userName,
            ]);
        });
    }
}