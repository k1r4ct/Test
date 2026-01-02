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

class ProductController extends Controller
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
    public function show(product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(product $product)
    {
        //
    }

    public function getProdotto($id)
    {
        $product = Product::with('supplier', 'macro_product', 'supplier.supplier_category', 'macro_product.supplier_category')->where('id', $id)->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["prodotto" => $product, "messaggio" => "Prodotto trovato"]]);
    }
    
    public function prodotti()
    {
        $prodotti = Product::with(['supplier:id,nome_fornitore,descrizione', 'macro_product'])->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["prodotti" => $prodotti, "messaggio" => "Prodotto trovato"]]);
    }

    public function storeNewProduct(Request $request)
    {
        if ($request->attivo) {
            $attivo = 1;
        } else {
            $attivo = 0;
        }
        $explodeDataInizioOfferta = explode('T', $request->inizioOfferta);
        $dataInizioOfferta = $explodeDataInizioOfferta[0];

        $explodeDataFineOfferta = explode('T', $request->fineOfferta);
        $dataFineOfferta = $explodeDataFineOfferta[0];

        $newProduct = product::create([
            'descrizione' => $request->descrizione,
            'supplier_id' => $request->supplier,
            'punti_valore' => 0,
            'punti_carriera' => 0,
            'attivo' => $attivo,
            'macro_product_id' => $request->macroProduct,
            'gettone' => $request->gettone,
            'inizio_offerta' => $dataInizioOfferta,
            'fine_offerta' => $dataFineOfferta,
        ]);

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $newProduct]]);
    }

    public function nuovoProdotto()
    {
        $supplier_category = supplier_category::all();
        $supplier = supplier::all();
        $macro_product = macro_product::all();
        return response()->json(["response" => "ok", "status" => "200", "body" =>
        [
            "risposta" =>
            [
                "supplier_category" => $supplier_category,
                "supplier" => $supplier,
                "macro_product" => $macro_product
            ]
        ]]);
    }


    public function disabilitaProdotto($id)
    {
        $prodotto = product::where('id', $id)->update(['attivo' => 0]);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => "disabilitato"]]);
    }

    public function abilitaProdotto($id)
    {
        $prodotto = product::where('id', $id)->update(['attivo' => 1]);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => "abilitato"]]);
    }

    public function cancellaProdotto($id)
    {
        $prodotto = product::where('id', $id)->delete();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => "cancellato"]]);
    }

    public function updateProdotto(Request $request)
    {
        $updateProdotto = product::where('id', $request->idProdotto)->update(['descrizione' => $request->descrizione,'attivo'=>$request->attivo]);
        $updateMacroProduct=$this->updateMacroProduct($request->idMacroProdotto,$request->descrizioneMacroProdotto,$updateProdotto);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $updateProdotto,"updateMacroProd"=>$updateMacroProduct]]);
    }

    public function updateMacroProduct($idMacroProduct,$descMacroProduct,$updateProdotto){
        $findAndUpdate=macro_product::find($idMacroProduct);
        if ($descMacroProduct!=$findAndUpdate->descrizione) {
            $findAndUpdate->update(['descrizione'=>$descMacroProduct]);
            $updateMP= "Macro Prodotto Modificato";
        }else {
            $updateMP= "Macro Prodotto non modificato";
        }
        return $updateMP;
    }
}