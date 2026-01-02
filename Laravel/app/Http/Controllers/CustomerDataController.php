<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\product;
use App\Models\contract;
use Illuminate\Support\Str;
use Hamcrest\Type\IsNumeric;
use Illuminate\Http\Request;
use App\Models\customer_data;
use App\Models\macro_product;
use App\Models\qualification;
use App\Models\specific_data;
use App\Http\Controllers\Controller;
use App\Models\backoffice_note;
use App\Models\contract_management;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\contract_type_information;
use App\Models\DetailQuestion;
use App\Models\lead;
use App\Models\lead_status;
use App\Models\leadConverted;
use App\Models\notification;
use App\Models\option_status_contract;
use App\Models\payment_mode;
use App\Models\status_contract;
use App\Models\supplier;
use App\Models\supplier_category;

class CustomerDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(customer_data $customer_data)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(customer_data $customer_data)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, customer_data $customer_data)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(customer_data $customer_data)
    {
        //
    }

    public function nuovoContraente()
    {




        $contraente = Customer_data::create([
            "nome" => request('nome'),
            "cognome" => request('cognome'),
            "email" => request('email'),
            "pec" => request('pec'),
            "codice_fiscale" => request('codice_fiscale'),
            "telefono" => request('telefono'),
            "indirizzo" => request('indirizzo'),
            "citta" => request('citta'),
            "cap" => request('cap'),
            "provincia" => request('provincia'),
            "nazione" => request('nazione'),
            "ragione_sociale" => request('ragione_sociale'),
            "partita_iva" => request('partita_iva'),
        ]);

        return response()->json(["response" => "ok", "status" => "200", "body" => ["id" => $contraente->id, "messaggio" => $contraente, "dati_ricevuti_in_request" => request()->all()]]);
    }
}
