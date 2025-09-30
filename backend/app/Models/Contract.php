<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable=[
        'codice_contratto',
        'id_inserito_da',
        'id_associato_a',
        'product_id',
        'customer_data_id',
        'specific_data_id',
        'data_inserimento',
        'data_stipula',
        'payment_mode_id',
        'status_contract_id',
        'document_data_id',
    ];


    public function User(){
        return $this->belongsTo(User::class);
    }

    public function customer_data(){
        return $this->belongsTo(Customer_data::class);
    }

    public function status_contract(){
        return $this->belongsTo(Status_contract::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function specific_data(){
        return $this->belongsTo(Specific_data::class);
    }

    public function payment_mode(){
        return $this->belongsTo(Payment_mode::class);
    }

    public function document_data(){
        return $this->belongsTo(Document_data::class);
    }
}
