<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_name',
    ];

    // Relationships
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status_name === 'attivo';
    }

    public function isPending()
    {
        return $this->status_name === 'in_attesa_di_pagamento';
    }

    public function isCompleted()
    {
        return $this->status_name === 'completato';
    }
}