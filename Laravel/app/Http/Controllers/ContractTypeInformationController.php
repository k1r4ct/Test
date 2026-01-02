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

class ContractTypeInformationController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function contractType()
    {

        $contractType = contract_type_information::all();

        return response()->json(["response" => "ok", "status" => "200", "body" => ["contractType" => $contractType]]);
    }

    public function getDomandeMacro(Request $request, $idMacroProduct)
    {

        // $dettagli_contratto = Contract::with(['product.macro_product'])
        //     ->where('id', $idcontratto)
        //     ->get();

        // $macroProductIds = $dettagli_contratto->pluck('product.macro_product.id')->filter()->unique()->values();
        $domandedaescludere = $request['domande'];

        // $Domande = contract_type_information::with(['detail_questions'])->where('macro_product_id', $macroProductIds[0])
        // ->whereNotIn('domanda', $domandedaescludere)
        // ->get();
        $Domande = contract_type_information::with(['DetailQuestion'])->where('macro_product_id', $idMacroProduct)
            ->whereNotIn('domanda', $domandedaescludere)
            ->get();

        //return response(["response" => "ok", "status" => "200", "body" => $idcontratto, "domanda" => $domanda, "macrop" => $macroProductIds[0], "rispostatipo" => $tipoRisposta]);
        return response(["response" => "ok", "status" => "200", "id_macro_prodotto"=> $idMacroProduct,  "ListaDomande" => $Domande, "domande_escluse" => $domandedaescludere]);
    }



    public function getRisposteSelect(Request $request, $idDomanda){

        $domanda = specific_data::where('id', $idDomanda)->get();
        $idcontratto = $domanda[0]->contract_id;
        $domandaSelect = $domanda[0]->domanda;        
        $contratto = Contract::with(['product.macro_product'])->where('id', $idcontratto)->get();
        $macrop = $contratto[0]->product->macro_product->id;
        $risposteSelect = contract_type_information::where('macro_product_id', $macrop)->where('domanda',$domandaSelect)->get();
        $id_detail_question = $risposteSelect[0]->id;

        $rispostadaescludere = $request['rispostafornita'];

        $listaRisposteSelect = DetailQuestion::where('contract_type_information_id', $id_detail_question)
        ->whereNot('opzione', $rispostadaescludere)->get();

        return response(["response" => "ok", "status" => "200", "body" => $listaRisposteSelect]);

    }


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
    public function show(contract_type_information $contract_type_information)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(contract_type_information $contract_type_information)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, contract_type_information $contract_type_information)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(contract_type_information $contract_type_information)
    {
        //
    }
}
