<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey_type_information extends Model
{
    use HasFactory;

    protected $fillable=[
        'domanda',
        'risposta_tipo_numero',
        'risposta_tipo_stringa',
    ];
}
