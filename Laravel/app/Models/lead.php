<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lead extends Model
{
    use HasFactory;

    protected $appends = ['is_converted'];
    protected $fillable = [
        'invitato_da_user_id',
        'nome',
        'cognome',
        'telefono',
        'email',
        'lead_status_id',
        'assegnato_a',
        'consenso'

    ];

    public function leadstatus()
    {
        return $this->belongsTo(lead_status::class, 'lead_status_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'assegnato_a'); //foreing kye dove User andrà a cercare nella colonna indicata "assegnato_a" della tabella Leads
    }

    public function leadConverted()
    {
        return $this->hasOne(leadConverted::class);
    }


    public function getIsConvertedAttribute()
    {
        return !is_null($this->leadConverted);
    }
}
