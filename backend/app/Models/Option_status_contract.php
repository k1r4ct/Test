<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option_status_contract extends Model
{
    use HasFactory;

    protected $fillable=[
        'macro_stato',
        'fase',
        'specifica',
        'genera_pv',
        'genera_pc',
        'status_contract_id',


    ];

    public function status_contract(){
    
        return $this->belongsTo(Status_contract::class);
    }
}
