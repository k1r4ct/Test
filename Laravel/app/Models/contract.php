<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contract extends Model
{
    use HasFactory;

    protected $fillable=[
        'codice_contratto',
        'inserito_da_user_id',
        'associato_a_user_id',
        'product_id',
        'customer_data_id',
        'data_inserimento',
        'data_stipula',
        'payment_mode_id',
        'status_contract_id',
    ];


    public function User(){
        return $this->belongsTo(User::class,'associato_a_user_id');
    }

    public function UserSeu(){
        return $this->belongsTo(User::class,'inserito_da_user_id');
    }

    public function customer_data(){
        return $this->belongsTo(customer_data::class);
    }

    public function status_contract(){
        return $this->belongsTo(status_contract::class);
    }

    public function product(){
        return $this->belongsTo(product::class);
    }

    public function specific_data(){
        return $this->hasMany(specific_data::class);
    }

    public function payment_mode(){
        return $this->belongsTo(payment_mode::class);
    }

    public function document_data(){
        return $this->belongsTo(document_data::class);
    }

    public function backofficeNote(){
        
        return $this->hasMany(backoffice_note::class);
    }

}
