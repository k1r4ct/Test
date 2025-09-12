<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
  use HasFactory;


  protected $fillable = [
    'descrizione',


  ];

  public function User()
  {
    return $this->hasMany(User::class);
  }

  public function leadstatus()
  {
    return $this->hasMany(lead_status::class);
  }

  public function OptionStatus(){
    return $this->hasMany(Option_status_contract::class);
  }
}
