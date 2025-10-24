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
        'is_digital',
        'available',
        'category_id',
        'store_id',
        'thumbnail_asset_id',
    ];

    protected $casts = [
        'pv_price' => 'integer',
        'is_digital' => 'boolean',
        'available' => 'boolean',
    ];

    // Relationships
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
        return $this->belongsToMany(Asset::class, 'article_assets')->orderBy('display_order', 'asc');
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

    // Scopes
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

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Helper methods
    public function isDigital()
    {
        return $this->is_digital === true;
    }

    public function isPhysical()
    {
        return $this->is_digital === false;
    }

    public function isVisibleToUser($user)
    {
        // Check both category and store filters
        $categoryVisible = $this->category && $this->category->isVisibleToUser($user);
        $storeVisible = $this->store && $this->store->isVisibleToUser($user);

        return $categoryVisible && $storeVisible && $this->available;
    }
}