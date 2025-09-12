<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class log extends Model
{
    use HasFactory;

    protected $fillable=[
        'tipo_di_operazione',
        'datetime',
        'user_id',
    ];

    public function User(){
        return $this->belongsTo(User::class);
    }
}
