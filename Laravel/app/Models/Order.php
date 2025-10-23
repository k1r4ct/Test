<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'total_pv',
        'order_status_id',
        'payment_mode_id',
        'order_date',
    ];

    protected $casts = [
        'total_pv' => 'integer',
        'order_date' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(payment_mode::class, 'payment_mode_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Events - Clean up cart and update user PV when order is completed
    protected static function booted()
    {
        static::updated(function ($order) {
            // Get the 'completato' status
            $completedStatus = OrderStatus::where('status_name', 'completato')->first();
            $canceledStatus = OrderStatus::where('status_name', 'annullato')->first();
            
            // ORDER COMPLETED
            if ($completedStatus && $order->order_status_id == $completedStatus->id) {
                // Check if this is a status change to avoid multiple executions
                if ($order->isDirty('order_status_id')) {
                    
                    $user = User::find($order->user_id);
                    if ($user) {
                        // 1. Deduct PV from user's available balance
                        // Check if user has punti_bonus to use first
                        $pvToDeduct = $order->total_pv;
                        
                        if ($user->punti_bonus && $user->punti_bonus > 0) {
                            if ($user->punti_bonus >= $pvToDeduct) {
                                // Use only bonus PV
                                $user->decrement('punti_bonus', $pvToDeduct);
                            } else {
                                // Use all bonus PV and remaining from accumulated PV
                                $remaining = $pvToDeduct - $user->punti_bonus;
                                $user->punti_bonus = 0;
                                $user->decrement('punti_valore_maturati', $remaining);
                                $user->save();
                            }
                        } else {
                            // Use only accumulated PV
                            $user->decrement('punti_valore_maturati', $pvToDeduct);
                        }
                        
                        // 2. Update user's punti_spesi counter
                        $user->increment('punti_spesi', $order->total_pv);
                    }
                    
                    // 3. Delete all cart items for this user with 'completato' status
                    $completedCartStatus = CartStatus::where('status_name', 'completato')->first();
                    if ($completedCartStatus) {
                        CartItem::where('user_id', $order->user_id)
                                ->where('cart_status_id', $completedCartStatus->id)
                                ->delete();
                    }
                }
            }
            
            // ORDER CANCELED - Release blocked PV
            if ($canceledStatus && $order->order_status_id == $canceledStatus->id) {
                if ($order->isDirty('order_status_id')) {
                    // Delete cart items to release blocked PV
                    $pendingCartStatus = CartStatus::where('status_name', 'in_attesa_di_pagamento')->first();
                    if ($pendingCartStatus) {
                        CartItem::where('user_id', $order->user_id)
                                ->where('cart_status_id', $pendingCartStatus->id)
                                ->delete();
                    }
                }
            }
        });
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('order_status_id', $statusId);
    }

    public function scopeCompleted($query)
    {
        return $query->whereHas('orderStatus', function($q) {
            $q->where('status_name', 'completato');
        });
    }
}