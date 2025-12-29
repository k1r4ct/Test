<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /**
     * The attribute set that defines which attributes this article can have.
     */
    public function attributeSet()
    {
        return $this->belongsTo(AttributeSet::class);
    }

    /**
     * All attribute values for this article.
     */
    public function attributeValues()
    {
        return $this->hasMany(ArticleAttributeValue::class);
    }

    /**
     * Get attributes through attribute values.
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'article_attribute_values')
                    ->withTimestamps();
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

    /**
     * Scope to filter articles by attribute value.
     */
    public function scopeWhereAttribute($query, string $attributeCode, $value)
    {
        return $query->whereHas('attributeValues', function ($q) use ($attributeCode, $value) {
            $q->whereHas('attribute', function ($attrQ) use ($attributeCode) {
                $attrQ->where('attribute_code', $attributeCode);
            });

            // Determine which column to filter based on attribute type
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

    public function isVisibleToUser($user)
    {
        // Check both category and store filters
        $categoryVisible = $this->category && $this->category->isVisibleToUser($user);
        $storeVisible = $this->store && $this->store->isVisibleToUser($user);

        return $categoryVisible && $storeVisible && $this->available;
    }

    /**
     * Get the euro price formatted for display.
     */
    public function getFormattedEuroPriceAttribute(): string
    {
        if ($this->euro_price === null) {
            return '';
        }
        return number_format($this->euro_price, 2, ',', '.') . ' €';
    }

    /**
     * Get the PV price formatted for display.
     */
    public function getFormattedPvPriceAttribute(): string
    {
        return number_format($this->pv_price, 0, ',', '.') . ' PV';
    }

    // ==================== EAV HELPER METHODS ====================

    /**
     * Get all attribute values as associative array [code => value].
     */
    public function getAttributeValuesArray(): array
    {
        return ArticleAttributeValue::getAllValues($this->id, true);
    }

    /**
     * Get a specific attribute value by code.
     */
    public function getAttributeValue(string $attributeCode)
    {
        return ArticleAttributeValue::getValueByCode($this->id, $attributeCode);
    }

    /**
     * Get a specific attribute value formatted for display.
     */
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

    /**
     * Set a specific attribute value by code.
     */
    public function setAttributeValue(string $attributeCode, $value): ArticleAttributeValue
    {
        return ArticleAttributeValue::setValueByCode($this->id, $attributeCode, $value);
    }

    /**
     * Set multiple attribute values at once.
     * 
     * @param array $values [attribute_code => value]
     */
    public function setAttributeValues(array $values): array
    {
        return ArticleAttributeValue::bulkSetValues($this->id, $values, true);
    }

    /**
     * Delete a specific attribute value.
     */
    public function deleteAttributeValue(string $attributeCode): bool
    {
        $attribute = Attribute::findByCode($attributeCode);
        if (!$attribute) {
            return false;
        }
        return ArticleAttributeValue::deleteValue($this->id, $attribute->id);
    }

    /**
     * Check if article has all required attributes filled.
     */
    public function hasAllRequiredAttributes(): bool
    {
        if (!$this->attributeSet) {
            return true; // No attribute set = no requirements
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

    /**
     * Get missing required attributes.
     */
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

    /**
     * Get available attributes for this article based on its attribute set.
     */
    public function getAvailableAttributes()
    {
        if (!$this->attributeSet) {
            return collect();
        }

        return $this->attributeSet->activeAttributes;
    }

    /**
     * Load article with all EAV data for display.
     */
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

    /**
     * Get total available stock across all stores.
     */
    public function getTotalStock(): int
    {
        return $this->stock()->sum('quantity');
    }

    /**
     * Check if article is in stock.
     */
    public function isInStock(): bool
    {
        return $this->getTotalStock() > 0;
    }

    /**
     * Get stock for a specific store.
     */
    public function getStockForStore(int $storeId): int
    {
        $stock = $this->stock()->where('store_id', $storeId)->first();
        return $stock ? $stock->quantity : 0;
    }
}