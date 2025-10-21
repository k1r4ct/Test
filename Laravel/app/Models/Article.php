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
        'available',
        'category_id',
        'filter_id',
        'store_id',
        'thumbnail_asset_id',
    ];

    protected $casts = [
        'pv_price' => 'integer',
        'available' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function filter()
    {
        return $this->belongsTo(Filter::class);
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

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByFilter($query, $filterId)
    {
        return $query->where('filter_id', $filterId);
    }
}