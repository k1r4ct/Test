<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPassword;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
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
        'codice',
        'name',
        'cognome',
        'ragione_sociale',
        'email',
        'pec',
        'password',
        'codice_fiscale',
        'partita_iva',
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
        'partita_iva',
        'ragione_sociale'
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

    public function Role()
    {
        return $this->belongsTo(Role::class);
    }

    public function qualification()
    {
        return $this->belongsTo(qualification::class);
    }

    public function lead()
    {
        return $this->hasMany(lead::class, 'assegnato_a');
    }

    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    public function survey()
    {
        return $this->hasMany(survey::class);
    }

    public function log()
    {
        return $this->hasMany(log::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'user_id_padre');
    }

    public function contract_management()
    {

        return $this->hasMany(contract_management::class, "user_id");
    }

    public function Notification()
    {
        return $this->hasMany(notification::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}
