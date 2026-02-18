<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsDatabaseOperations;

class supplier extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable=[
        'nome_fornitore',
        'descrizione',
        'supplier_category_id',
    ];

    public function supplier_category(){
        return $this->belongsTo(supplier_category::class);
    }

    public function product(){
        return $this->hasMany(product::class);
    }
}
