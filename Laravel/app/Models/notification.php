<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'reparto',
        'notifica',
        'visualizzato',
        'notifica_html',
    ];

    public function User(){
        return $this->hasMany(User::class,'to_user_id');
    }
}
