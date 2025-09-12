<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class backoffice_note extends Model
{
    use HasFactory;

    protected $fillable=[
        'contract_id',
        'nota',
    ];

    public function contract(){
        
        return $this->belongsTo(Contract::class);
    }
}
