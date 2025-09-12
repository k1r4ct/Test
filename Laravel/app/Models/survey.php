<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class survey extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id',
        'domanda',
        'tipo_risposta',

    ];

    public function User(){
        return $this->belongsTo(User::class);
    }
}
