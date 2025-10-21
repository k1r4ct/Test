<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'article_id',
        'quantity',
        'pv_temporanei',
        'cart_status_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'pv_temporanei' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function cartStatus()
    {
        return $this->belongsTo(CartStatus::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('cartStatus', function($q) {
            $q->where('status_name', 'attivo');
        });
    }

    public function scopePending($query)
    {
        return $query->whereHas('cartStatus', function($q) {
            $q->where('status_name', 'in_attesa_di_pagamento');
        });
    }

    // Helper methods
    public function getTotalPv()
    {
        return $this->quantity * $this->article->pv_price;
    }
}