<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class macro_product extends Model
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
        return $this->belongsTo(supplier_category::class);
    }

    public function product(){
        return $this->hasMany(product::class);
    }

    public function contract_type_information(){
        return $this->hasMany(contract_type_information::class);
    }

    public function contract_management(){
    
        return $this->hasMany(contract_management::class,"user_id");
    }

    public function contract_managementProduct(){
    
        return $this->hasMany(contract_management::class,"macro_product_id");
    }
}
