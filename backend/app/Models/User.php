<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'qualification_id',
        'name',
        'cognome',
        'email',
        'pec',
        'password',
        'codice_fiscale',
        'telefono',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'nazione',
        'stato_user',
        'punti_valore_maturati',
        'punti_carriera_maturati',
        'user_id_padre',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function Role(){
        return $this->belongsTo(Role::class);
    }

    public function qualification(){
        return $this->belongsTo(qualification::class);
    }

    public function lead(){
        return $this->hasMany(lead::class);
    }

    public function contract(){
        return $this->hasMany(contract::class);
    }

    public function survey(){
        return $this->hasMany(survey::class);
    }

    public function log(){
        return $this->hasMany(log::class);
    }
}
