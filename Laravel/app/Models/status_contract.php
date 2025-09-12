<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class status_contract extends Model
{
    use HasFactory;

    protected $fillable=[
        'micro_stato',


    ];

    public function contract(){
        return $this->hasMany(contract::class);
    }

    public function option_status_contract(){
        return $this->hasMany(option_status_contract::class);
    }
}
