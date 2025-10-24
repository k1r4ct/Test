<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

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

    // Relationships
    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Scopes
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

    // Helper methods
    public function isLowStock()
    {
        return $this->quantity <= $this->minimum_stock;
    }

    public function isOutOfStock()
    {
        return $this->quantity <= 0;
    }
}