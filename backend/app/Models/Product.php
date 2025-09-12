<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable=[
        'descrizione',
        'supplier_id',
        'punti_carriera',
        'punti_valore',
        'attivo',
        'macro_product_id',
        'gettone',
        'inizio_offerta',
        'fine_offerta',
    ];
    
    public function contract(){
        return $this->hasMany(Contract::class);
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }

    public function macro_product(){
        return $this->belongsTo(Macro_product::class);
    }
}
