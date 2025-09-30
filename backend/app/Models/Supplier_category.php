<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier_category extends Model
{
    use HasFactory;

    protected $fillable=[
        'nome_categoria',
        'descrizione',


    ];

    public function supplier(){
        return $this->hasMany(Supplier::class);
    }

    public function macro_product(){
        return $this->hasMany(Macro_product::class);
    }
}
