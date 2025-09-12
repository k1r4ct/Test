<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable=[
        'nome_fornitore',
        'descrizione',
        'supplier_category_id',
    ];

    public function supplier_category(){
        return $this->belongsTo(Supplier_category::class);
    }

    public function product(){
        return $this->hasMany(Product::class);
    }
}
