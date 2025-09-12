<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class document_data extends Model
{
    use HasFactory;

    protected $fillable=[
        'tipo',
        'descrizione',
        'path_storage',
    ];

    public function contract(){
        return $this->hasMany(contract::class);
    }
}
