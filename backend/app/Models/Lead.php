<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable=[
        'inviato_da',
        'nome',
        'cognome',
        'telefono',
        'email',
        'lead_status',

    ];

    public function leadstatus(){
        return $this->belongsTo(Lead_status::class);
    }

    public function User(){
        return $this->belongsTo(User::class);
    }
}
