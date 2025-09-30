<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Macro_product extends Model
{
    use HasFactory;

    protected $fillable=[
        'codice_macro',
        'descrizione',
        'punti_valore',
        'punti_carriera',
        'supplier_category_id',
    ];

    public function supplier_category(){
        return $this->belongsTo(Supplier_category::class);
    }

    public function product(){
        return $this->hasMany(Product::class);
    }

    public function contract_type_information(){
        return $this->hasMany(Contract_type_information::class);
    }
}
