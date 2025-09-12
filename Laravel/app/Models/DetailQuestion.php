<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailQuestion extends Model
{
    use HasFactory;

    protected $fillable=[
        'contract_type_information_id',
        'opzione'
    ];

    public function CtypeInfo(){
        return $this->belongsTo(contract_type_information::class);
    }
}
