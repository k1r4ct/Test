<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'colore'
    ];

    public function LeadStatus(){
       return $this->belongsTo(Lead_status::class);
    }
}
