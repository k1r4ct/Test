<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class payment_mode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_pagamento',
        'payment_type',
    ];

    protected $casts = [
        'payment_type' => 'string',
    ];

    // Relationships
    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'payment_mode_id');
    }

    // Helper methods
    public function isInstant()
    {
        return $this->payment_type === 'instant';
    }

    public function isElectronic()
    {
        return $this->payment_type === 'electronic';
    }

    public function isManual()
    {
        return $this->payment_type === 'manual';
    }

    // Scopes
    public function scopeInstant($query)
    {
        return $query->where('payment_type', 'instant');
    }

    public function scopeElectronic($query)
    {
        return $query->where('payment_type', 'electronic');
    }
}