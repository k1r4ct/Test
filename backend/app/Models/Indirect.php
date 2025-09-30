<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indirect extends Model
{
    use HasFactory;

    protected $fillable=[
        'numero_livello',
        'percentuale_indiretta',
        'qualification_id',

    ];


    public function qualification(){
        return $this->belongsTo(Qualification::class);
    }
}
