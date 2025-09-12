<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class customer_data extends Model
{
    use HasFactory;

    protected $fillable=[
        'nome',
        'cognome',
        'email',
        'pec',
        'codice_fiscale',
        'telefono',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'nazione',
        'ragione_sociale',
        'partita_iva',
    ];

    public function contract(){
        return $this->hasMany(contract::class);
    }
}
