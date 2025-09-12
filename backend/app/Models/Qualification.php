<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    use HasFactory;

    protected $fillable=[
        
        'descrizione',
        'pc_necessari',
        'compenso_pvdiretti',
        'pc_bonus_mensile',
    ];

    public function User(){
        return $this->hasMany(User::class);
    }

    public function indirect(){
        return $this->hasMany(Indirect::class);
    }
}
