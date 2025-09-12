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


class SpecificDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getDomande($id)
    {

        $prodotto = Product::where('id', $id)->get();
        foreach ($prodotto as $prod) {
            $elenco = contract_type_information::with('DetailQuestion')->where('macro_product_id', $prod->macro_product_id)->get();
        }
        /* foreach ($elenco as $option) {
            $getOption=
        } */
        return response()->json(["response" => "ok", "status" => "200", "body" => ["Domande" => $elenco]]);
    }

    public function getListaDomande()
    {

        $elenco = contract_type_information::with(['macro_product', 'DetailQuestion'])->orderBy('macro_product_id')->get();

        return response()->json(["response" => "ok", "status" => "200", "body" => ["Domande" => $elenco]]);
    }

    public function salvaDomande(Request $request)
    {
        foreach ($request->all() as $key => $req) {
            if (isset($req['questionId'])) {
                $r = $req['questionId'];
                //$count = count($req['options']);
                //$optdel = $req['opzioniRimosse'];
                if ($r != 0 || $r != null) {
                    $contractTypeInfo = contract_type_information::where('id', $r)->update([
                        'domanda' => $req['domanda'],
                        'obbligatorio' => $req['obbligatorio'],
                        'tipo_risposta' => $req['tipo_risposta'],
                    ]);
                    if (count($req['options']) > 0) {
                        foreach ($req['options'] as $key => $opt) {
                            $id = $opt['id'];
                            $opzione = $opt['opt'];
                            $detailQuestions = DetailQuestion::where('id', "=", $id)->update([
                                'opzione' => $opzione,
                            ]);

                            $catId = $opt;
                        }
                    }
                    if (is_array($req['opzioniRimosse']) && count($req['opzioniRimosse']) > 0) {
                        foreach ($req['opzioniRimosse'] as $opt) {
                            $detailQuestions = DetailQuestion::where('id', "=", $opt)->delete();
                        }
                    }
                    if (is_array($req['opzioniAggiuntive']) && count($req['opzioniAggiuntive']) > 0) {
                        foreach ($req['opzioniAggiuntive'] as $opt) {
                            $detailQuestions = DetailQuestion::create([
                                'contract_type_information_id' => $r,
                                'opzione' => $opt,
                            ]);
                        }
                    }
                }
            } else if (isset($req['categoryId'])) {

                foreach ($request->all() as $key => $req) {
                    $r = $req['type'];
                    $contractTypeInfo = contract_type_information::create([
                        'macro_product_id' => $req['categoryId'],
                        'domanda' => $req['text'],
                        'tipo_risposta' => $req['type'],
                        'obbligatorio' => $req['obbligatorio'],
                    ]);
                    if (count($req['options']) > 0) {
                        foreach ($req['options'] as $key) {
                            $catId = $key;
                            $detailQuestions = DetailQuestion::create([
                                'contract_type_information_id' => $contractTypeInfo->id,
                                'opzione' => $key,
                            ]);
                        }
                    }
                }
            }
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $request->all()]]);
    }

    public function deleteQuestion(Request $request, $id)
    {

        $delete1 = contract_type_information::find($id);
        $delete2 = detailQuestion::where('contract_type_information_id', $delete1->id)->delete();
        $delete1->delete();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $delete1->id]]);
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
    public function show(specific_data $specific_data)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(specific_data $specific_data)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, specific_data $specific_data)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(specific_data $specific_data)
    {
        //
    }
}
