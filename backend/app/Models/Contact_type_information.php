<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact_type_information extends Model
{
    use HasFactory;

    protected $fillable=[
        'macro_product_id',
        'domanda',
        'descrizione',
    ];

    public function macro_product(){
        return $this->belongsTo(Macro_product::class);
    }
}
