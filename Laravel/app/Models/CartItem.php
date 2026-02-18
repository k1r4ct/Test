<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use App\Traits\LogsDatabaseOperations;

class CartItem extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'user_id',
        'article_id',
        'quantity',
        'pv_bloccati',
        'cart_status_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'pv_bloccati' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

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

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log item added to cart
        static::created(function ($cartItem) {
            // Load relationships for logging
            $cartItem->load(['user', 'article']);
            
            $userName = $cartItem->user 
                ? $cartItem->user->name . ' ' . $cartItem->user->cognome 
                : 'User #' . $cartItem->user_id;
            
            $articleName = $cartItem->article 
                ? $cartItem->article->article_name 
                : 'Article #' . $cartItem->article_id;

            SystemLogService::ecommerce()->info("Cart item added", [
                'cart_item_id' => $cartItem->id,
                'user_id' => $cartItem->user_id,
                'user_name' => $userName,
                'article_id' => $cartItem->article_id,
                'article_name' => $articleName,
                'quantity' => $cartItem->quantity,
                'pv_bloccati' => $cartItem->pv_bloccati,
                'cart_status_id' => $cartItem->cart_status_id,
            ]);
        });

        // Log cart item updates (quantity changes, status changes)
        static::updated(function ($cartItem) {
            $changes = $cartItem->getChanges();
            $original = $cartItem->getOriginal();

            // Build changes array for logging
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
                // Load relationships for logging
                $cartItem->load(['user', 'article']);
                
                $userName = $cartItem->user 
                    ? $cartItem->user->name . ' ' . $cartItem->user->cognome 
                    : 'User #' . $cartItem->user_id;
                
                $articleName = $cartItem->article 
                    ? $cartItem->article->article_name 
                    : 'Article #' . $cartItem->article_id;

                SystemLogService::ecommerce()->info("Cart item updated", [
                    'cart_item_id' => $cartItem->id,
                    'user_id' => $cartItem->user_id,
                    'user_name' => $userName,
                    'article_id' => $cartItem->article_id,
                    'article_name' => $articleName,
                    'changes' => $changesForLog,
                ]);
            }
        });

        // Log cart item removal
        static::deleted(function ($cartItem) {
            // Try to load relationships if not already loaded
            $userName = 'User #' . $cartItem->user_id;
            $articleName = 'Article #' . $cartItem->article_id;
            
            if ($cartItem->relationLoaded('user') && $cartItem->user) {
                $userName = $cartItem->user->name . ' ' . $cartItem->user->cognome;
            }
            
            if ($cartItem->relationLoaded('article') && $cartItem->article) {
                $articleName = $cartItem->article->article_name;
            }

            SystemLogService::ecommerce()->info("Cart item removed", [
                'cart_item_id' => $cartItem->id,
                'user_id' => $cartItem->user_id,
                'user_name' => $userName,
                'article_id' => $cartItem->article_id,
                'article_name' => $articleName,
                'quantity_removed' => $cartItem->quantity,
                'pv_released' => $cartItem->pv_bloccati,
            ]);
        });
    }

    // ==================== SCOPES ====================

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('cartStatus', function ($q) {
            $q->where('status_name', 'attivo');
        });
    }

    public function scopePending($query)
    {
        return $query->whereHas('cartStatus', function ($q) {
            $q->where('status_name', 'in_attesa_di_pagamento');
        });
    }

    // ==================== HELPER METHODS ====================

    public function getTotalPv()
    {
        return $this->quantity * $this->article->pv_price;
    }

    /**
     * Check if this cart item belongs to a specific user
     */
    public function belongsToUser(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Update quantity and recalculate blocked PV
     */
    public function updateQuantity(int $newQuantity): bool
    {
        if ($newQuantity <= 0) {
            return false;
        }

        $this->quantity = $newQuantity;
        
        // Recalculate blocked PV if article is loaded
        if ($this->article) {
            $this->pv_bloccati = $newQuantity * $this->article->pv_price;
        }

        return $this->save();
    }
}