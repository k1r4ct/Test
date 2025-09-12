<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class supplier_category extends Model
{
    use HasFactory;

    protected $fillable=[
        'nome_categoria',
        'descrizione',


    ];

    public function supplier(){
        return $this->hasMany(supplier::class);
    }

    public function macro_product(){
        return $this->hasMany(macro_product::class);
    }
}
