<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'article_name',
        'description',
        'pv_price',
        'euro_price',
        'is_digital',
        'available',
        'sort_order',
        'is_featured',
        'is_bestseller',
        'category_id',
        'store_id',
        'attribute_set_id',
        'thumbnail_asset_id',
    ];

    protected $casts = [
        'pv_price' => 'integer',
        'euro_price' => 'decimal:2',
        'is_digital' => 'boolean',
        'available' => 'boolean',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
    ];

    // Fields that are important to track in logs
    protected static $importantFields = [
        'pv_price',
        'euro_price',
        'available',
        'is_digital',
        'is_featured',
        'is_bestseller',
        'category_id',
        'store_id',
    ];

    // ==================== RELATIONSHIPS ====================

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function thumbnail()
    {
        return $this->belongsTo(Asset::class, 'thumbnail_asset_id');
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'article_assets')
                    ->orderBy('display_order', 'asc');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // ==================== EAV RELATIONSHIPS ====================

    public function attributeSet()
    {
        return $this->belongsTo(AttributeSet::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(ArticleAttributeValue::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'article_attribute_values')
                    ->withTimestamps();
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log article creation
        static::created(function ($article) {
            $article->load(['category', 'store']);
            
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Article created", [
                'article_id' => $article->id,
                'sku' => $article->sku,
                'article_name' => $article->article_name,
                'pv_price' => $article->pv_price,
                'euro_price' => $article->euro_price,
                'available' => $article->available,
                'is_digital' => $article->is_digital,
                'is_featured' => $article->is_featured,
                'is_bestseller' => $article->is_bestseller,
                'category_id' => $article->category_id,
                'category_name' => $article->category?->category_name,
                'store_id' => $article->store_id,
                'store_name' => $article->store?->store_name,
                'created_by' => $userName,
            ]);
        });

        // Log important updates
        static::updated(function ($article) {
            $changes = $article->getChanges();
            $original = $article->getOriginal();

            $changesForLog = [];
            $hasImportantChanges = false;

            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];

                    if (in_array($field, static::$importantFields)) {
                        $hasImportantChanges = true;
                    }
                }
            }

            if ($hasImportantChanges && !empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                SystemLogService::ecommerce()->info("Article updated", [
                    'article_id' => $article->id,
                    'sku' => $article->sku,
                    'article_name' => $article->article_name,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log deletion
        static::deleted(function ($article) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Article soft deleted", [
                'article_id' => $article->id,
                'sku' => $article->sku,
                'article_name' => $article->article_name,
                'deleted_by' => $userName,
            ]);
        });

        // Log force deletion
        static::forceDeleted(function ($article) {
            SystemLogService::ecommerce()->warning("Article permanently deleted", [
                'article_id' => $article->id,
                'sku' => $article->sku,
                'article_name' => $article->article_name,
            ]);
        });
    }

    // ==================== SCOPES ====================

    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    public function scopeDigital($query)
    {
        return $query->where('is_digital', true);
    }

    public function scopePhysical($query)
    {
        return $query->where('is_digital', false);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter bestseller articles.
     */
    public function scopeBestseller($query)
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAttributeSet($query, $attributeSetId)
    {
        return $query->where('attribute_set_id', $attributeSetId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopePriceRange($query, $minPv, $maxPv)
    {
        return $query->whereBetween('pv_price', [$minPv, $maxPv]);
    }

    public function scopeWhereAttribute($query, string $attributeCode, $value)
    {
        return $query->whereHas('attributeValues', function ($q) use ($attributeCode, $value) {
            $q->whereHas('attribute', function ($attrQ) use ($attributeCode) {
                $attrQ->where('attribute_code', $attributeCode);
            });

            $attribute = Attribute::where('attribute_code', $attributeCode)->first();
            if ($attribute) {
                $column = $attribute->getValueColumn();
                $q->where($column, $value);
            }
        });
    }

    // ==================== HELPER METHODS ====================

    public function isDigital()
    {
        return $this->is_digital === true;
    }

    public function isPhysical()
    {
        return $this->is_digital === false;
    }

    public function isFeatured()
    {
        return $this->is_featured === true;
    }

    /**
     * Check if article is marked as bestseller.
     */
    public function isBestseller()
    {
        return $this->is_bestseller === true;
    }

    public function isVisibleToUser($user)
    {
        $categoryVisible = $this->category && $this->category->isVisibleToUser($user);
        $storeVisible = $this->store && $this->store->isVisibleToUser($user);

        return $categoryVisible && $storeVisible && $this->available;
    }

    public function getFormattedEuroPriceAttribute(): string
    {
        if ($this->euro_price === null) {
            return '';
        }
        return number_format($this->euro_price, 2, ',', '.') . ' €';
    }

    public function getFormattedPvPriceAttribute(): string
    {
        return number_format($this->pv_price, 0, ',', '.') . ' PV';
    }

    // ==================== EAV HELPER METHODS ====================

    public function getAttributeValuesArray(): array
    {
        return ArticleAttributeValue::getAllValues($this->id, true);
    }

    public function getEavAttributeValue(string $attributeCode)
    {
        return ArticleAttributeValue::getValueByCode($this->id, $attributeCode);
    }

    public function getFormattedAttributeValue(string $attributeCode): string
    {
        $attrValue = $this->attributeValues()
            ->whereHas('attribute', function ($q) use ($attributeCode) {
                $q->where('attribute_code', $attributeCode);
            })
            ->with('attribute')
            ->first();

        return $attrValue ? $attrValue->getFormattedValue() : '';
    }

    public function setAttributeValue(string $attributeCode, $value): ArticleAttributeValue
    {
        return ArticleAttributeValue::setValueByCode($this->id, $attributeCode, $value);
    }

    public function setAttributeValues(array $values): array
    {
        return ArticleAttributeValue::bulkSetValues($this->id, $values, true);
    }

    public function deleteAttributeValue(string $attributeCode): bool
    {
        $attribute = Attribute::findByCode($attributeCode);
        if (!$attribute) {
            return false;
        }
        return ArticleAttributeValue::deleteValue($this->id, $attribute->id);
    }

    public function hasAllRequiredAttributes(): bool
    {
        if (!$this->attributeSet) {
            return true;
        }

        $requiredIds = $this->attributeSet->getRequiredAttributeIds();
        $filledIds = $this->attributeValues()
                          ->whereIn('attribute_id', $requiredIds)
                          ->whereNotNull('value_text')
                          ->orWhereNotNull('value_textarea')
                          ->orWhereNotNull('value_integer')
                          ->orWhereNotNull('value_decimal')
                          ->orWhereNotNull('value_boolean')
                          ->orWhereNotNull('value_date')
                          ->orWhereNotNull('value_datetime')
                          ->orWhereNotNull('value_json')
                          ->pluck('attribute_id')
                          ->toArray();

        return empty(array_diff($requiredIds, $filledIds));
    }

    public function getMissingRequiredAttributes(): array
    {
        if (!$this->attributeSet) {
            return [];
        }

        $requiredIds = $this->attributeSet->getRequiredAttributeIds();
        $filledIds = $this->attributeValues()->pluck('attribute_id')->toArray();
        $missingIds = array_diff($requiredIds, $filledIds);

        return Attribute::whereIn('id', $missingIds)->get()->toArray();
    }

    public function getAvailableAttributes()
    {
        if (!$this->attributeSet) {
            return collect();
        }

        return $this->attributeSet->activeAttributes;
    }

    public function loadWithAttributes(): self
    {
        return $this->load([
            'attributeSet',
            'attributeValues.attribute',
            'category',
            'store',
            'thumbnail',
        ]);
    }

    // ==================== STOCK HELPER METHODS ====================

    public function getTotalStock(): int
    {
        return $this->stock()->sum('quantity');
    }

    public function isInStock(): bool
    {
        return $this->getTotalStock() > 0;
    }

    public function getStockForStore(int $storeId): int
    {
        $stock = $this->stock()->where('store_id', $storeId)->first();
        return $stock ? $stock->quantity : 0;
    }
}