<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead_status extends Model
{
    use HasFactory;

    protected $fillable=[
        'applicabile_da',
        'micro_stato',
        'macro_stato',
        'fase',
        'specifica',

    ];


    public function Role(){
        return $this->belongsTo(Role::class);
    }

    public function lead(){
        return $this->hasMany(Lead::class);
    }
}
