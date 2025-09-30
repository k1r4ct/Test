<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment_mode extends Model
{
    use HasFactory;

    protected $fillable=[
        'tipo_pagamento',
    ];

    public function contract(){
        return $this->hasMany(Contract::class);
    }
}
