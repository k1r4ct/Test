<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contract_management extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id',
        'macro_product_id',
    ];

    public function user(){
        return $this->belongsTo(user::class);
    }

    public function macro_product(){
        return $this->belongsTo(macro_product::class);
    }

    
}
