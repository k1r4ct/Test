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
use Illuminate\Support\Facades\Log;
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

class MacroProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function macroCat()
    {
        $macroCat = Macro_product::all();

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $macroCat]]);
    }

    public function getMacroProduct($id)
    {
        $supplier = supplier::find($id);
        $getMacroProduct = macro_product::where('supplier_category_id', $supplier->supplier_category_id)->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $getMacroProduct]]);
    }

    public function allMacroProduct($id)
    {

        $getMacroProduct = macro_product::with('product')->where('id', $id)->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $getMacroProduct]]);
    }

    public function GetallMacroProduct()
    {

        $getMacroProduct = macro_product::with('product')->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $getMacroProduct]]);
    }


    public function updateMacroProdotto(Request $request)
    {

        $updateMacroProdotto = macro_product::where('id', $request->id)
        ->update([
            'descrizione' => $request->descrizione ,
            'codice_macro' => $request->codice_macro ,
            'punti_valore' => $request->punti_valore,
            'punti_carriera' => $request->punti_carriera
            ]);

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $updateMacroProdotto]]);
    }
    
    public function creaNuovoMacroProdotto(Request $request)
    {

        $creaNuovoMacroProdotto = macro_product::create([
            'descrizione' => $request->descrizione ,
            'codice_macro' => $request->codice_macro ,
            'punti_valore' => $request->punti_valore,
            'punti_carriera' => $request->punti_carriera,
            'supplier_category_id' => $request->supplier_category_id
            ]);

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $creaNuovoMacroProdotto]]);
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
    public function show(macro_product $macro_product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(macro_product $macro_product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, macro_product $macro_product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(macro_product $macro_product)
    {
        //
    }
}
