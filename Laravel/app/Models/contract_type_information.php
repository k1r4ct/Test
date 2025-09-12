<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contract_type_information extends Model
{
    use HasFactory;

    protected $fillable=[
        'macro_product_id',
        'domanda',
        'tipo_risposta',
        'obbligatorio',
    ];

    public function macro_product(){
        return $this->belongsTo(macro_product::class);
    }

    public function DetailQuestion(){
        return $this->hasMany(DetailQuestion::class);
    }
}
