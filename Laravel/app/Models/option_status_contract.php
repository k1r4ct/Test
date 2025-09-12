<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class option_status_contract extends Model
{
    use HasFactory;

    protected $fillable=[
        'macro_stato',
        'fase',
        'specifica',
        'genera_pv',
        'genera_pc',
        'status_contract_id',
        'applicabile_da_role_id',


    ];

    public function status_contract(){
    
        return $this->belongsTo(status_contract::class);
    }

    public function Role(){
    
        return $this->belongsTo(Role::class,'applicabile_da_role_id');
    }
}
