<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class specific_data extends Model
{
    use HasFactory;

    protected $fillable=[
        'domanda',
        'risposta_tipo_numero',
        'risposta_tipo_stringa',
        'risposta_tipo_bool',
        'tipo_risposta',
        'contract_id',
    ];

    public function contract(){
        return $this->hasMany(contract::class);
    }
}
