<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specific_data extends Model
{
    use HasFactory;

    protected $fillable=[
        'domanda',
        'risposta_tipo_numero',
        'risposta_tipo_stringa',
    ];

    public function contract(){
        return $this->hasMany(Contract::class);
    }
}
