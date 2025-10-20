<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\product;
use App\Models\contract;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
use App\Models\log as ModelsLog;
use App\Models\notification;
use App\Models\option_status_contract;
use App\Models\payment_mode;
use App\Models\status_contract;
use App\Models\supplier;
use App\Models\supplier_category;


class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function nuovoContratto(Request $request)
    {
        $idCliente = request('id_cliente');
        $id_contraente = request('id_contraente');
        $id_prodotto = request('id_prodotto');
        $id_utente = request('id_utente');
        $nome_prodotto = request('nome_prodotto');
        $opt_prodotto = request('opt_prodotto');


        $statoContratto = request('stato_contratto');
        $tipoCliente = request('tipoCliente');
        $tipoContraente = request('tipoContraente');
        $dataStipula = request('data_Stipula');

        $anno = $dataStipula['year'];
        $mese = $dataStipula['month'];
        $giorno = $dataStipula['day'];
        $dataStipulaStringa = sprintf("%04d-%02d-%02d", $anno, $mese, $giorno);
        $formatData = Carbon::parse($dataStipulaStringa)->format("Y-m-d");

        $metodoDiPagamento = request('tipo_pagamento');
        $newContratto = Contract::create([
            "codice_contratto" => Str::random(9),
            "inserito_da_user_id" => $id_utente,
            "associato_a_user_id" => $idCliente,
            "product_id" => $id_prodotto,
            "customer_data_id" => $id_contraente,
            "data_inserimento" => Carbon::now()->format("Y-m-d"),
            "data_stipula" => $formatData,
            "payment_mode_id" => $metodoDiPagamento,
            "status_contract_id" => 1
        ]);

        // Decodifica il JSON se è una stringa, altrimenti usa direttamente l'array
        $optProdottoArray = is_string($opt_prodotto) ? json_decode($opt_prodotto, true) : $opt_prodotto;

        // Debug: Log per vedere cosa arriva dal frontend
        Log::info('Dati ricevuti dal frontend:', ['opt_prodotto' => $optProdottoArray]);

        foreach ($optProdottoArray as $item) {
            // Estrai i dati dal nuovo formato strutturato
            $domanda = $item['domanda'];
            $tipo_risposta = $item['tipo_risposta']; // usa il tipo già determinato dal frontend

            // Usa i valori già mappati dal frontend
            $risposta_tipo_stringa = $item['risposta_tipo_stringa'];
            $risposta_tipo_numero = $item['risposta_tipo_numero'];
            $risposta_tipo_bool = $item['risposta_tipo_bool'];

            // Aggiungi anche le informazioni aggiuntive se servono
            $obbligatorio = isset($item['obbligatorio']) ? $item['obbligatorio'] : false;
            $opzioni_disponibili = isset($item['opzioni']) ? json_encode($item['opzioni']) : null;

            // Debug: Log per ogni singolo item
            Log::info('Item da salvare:', [
                'domanda' => $domanda,
                'tipo_risposta' => $tipo_risposta,
                'risposta_tipo_stringa' => $risposta_tipo_stringa,
                'risposta_tipo_numero' => $risposta_tipo_numero,
                'risposta_tipo_bool' => $risposta_tipo_bool
            ]);

            $specific_datas = Specific_data::create([
                'domanda' => $domanda,
                "risposta_tipo_numero" => $risposta_tipo_numero,
                "risposta_tipo_stringa" => $risposta_tipo_stringa,
                "risposta_tipo_bool" => $risposta_tipo_bool,
                "tipo_risposta" => $tipo_risposta, // aggiungi questa colonna al database se non esiste
                "contract_id" => $newContratto->id,
                // Opzionali: aggiungi queste colonne se vuoi salvare più informazioni
                // "obbligatorio" => $obbligatorio,
                // "opzioni_disponibili" => $opzioni_disponibili,
            ]);

            // Debug: Log per confermare il salvataggio
            Log::info('Record salvato:', ['id' => $specific_datas->id, 'tipo_risposta' => $specific_datas->tipo_risposta]);
        }


        return response()->json(["response" => "ok", "status" => "200", "body" => ["id_Contratto" => $newContratto->id]]);
    }

    public function getContCodFPIva(Request $request)
    {

        $CFPI = $request->codFPIva;
        $tiporicerca = $request->tiporicerca;

        // $contrattiUtente = Contract::with([
        //     'User', 'customer_data',  'status_contract', 'status_contract.option_status_contract' , 'product', 'product.macro_product', 'specific_data', 'payment_mode'
        // ])->whereIn('product_id',)->get();

        // $contrattiUtente = Contract::with([
        //     'User', 'customer_data',  'status_contract', 'status_contract.option_status_contract' , 'product', 'product.macro_product', 'specific_data', 'payment_mode'
        // ])->all();


        if ($tiporicerca == "CODICE FISCALE") {
            $contrattiUtente = Contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])
                ->orWhereHas('User', function ($query) use ($CFPI) {
                    $query->Where('codice_fiscale', $CFPI);
                })
                ->orWhereHas('customer_data', function ($query) use ($CFPI) {
                    $query->Where('codice_fiscale', $CFPI);
                })
                ->get();
        } else {
            $contrattiUtente = Contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])
                ->orWhereHas('User', function ($query) use ($CFPI) {
                    $query->Where('partita_iva', $CFPI);
                })
                ->orWhereHas('customer_data', function ($query) use ($CFPI) {
                    $query->Where('partita_iva', $CFPI);
                })
                ->get();
        }

        foreach ($contrattiUtente as $contratto) {
            // Assumiamo che le tue date siano in colonne chiamate 'data_inserimento' e 'data_stipula'
            $contratto->data_inserimento = Carbon::parse($contratto->data_inserimento)->format('d-m-Y');
            $contratto->data_stipula = Carbon::parse($contratto->data_stipula)->format('d-m-Y');
        }
        // foreach ($contrattiUtente as $contratto) {
        //     // Ottieni un oggetto Carbon per ogni data
        //     $data_inserimento = \Carbon\Carbon::parse($contratto->data_inserimento);
        //     $data_stipula = \Carbon\Carbon::parse($contratto->data_stipula);

        //     // Formatta le date in italiano
        //     $contratto->data_inserimento = $data_inserimento->format('d/m/Y');
        //     $contratto->data_stipula = $data_stipula->format('d/m/Y');
        // }

        return response()->json([
            "response" => "ok",
            "status" => "300",
            "body" => ["risposta" => $contrattiUtente, "dati_request" => $request->all()]
        ]);
    }
    private function getTeamMemberIds(int $userId): array
    {
        $ids = [$userId];
        $stack = [$userId];

        while (!empty($stack)) {
            $current = array_pop($stack);
            $children = User::where('user_id_padre', $current)->pluck('id')->all();
            if (!empty($children)) {
                $ids = array_merge($ids, $children);
                $stack = array_merge($stack, $children);
            }
        }
        Log::info('Team Member IDs (including self): ' . implode(', ', $ids));
        return array_values(array_unique($ids));
    }

    public function getContratti(Request $request, $id)
    {
        // Parametri di paginazione comuni per tutti i ruoli
        $perPage = $request->get('per_page', 250); // Default 250, personalizzabile

        if (Auth::user()->role_id == 1) {
            $contrattiUtente = Contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode',
                'status_contract.option_status_contract'
            ])->orderBy('id', 'desc')->paginate($perPage);
        } elseif (Auth::user()->role_id == 2  || Auth::user()->role_id == 4) {

            // function getTeamMemberIds($userId, $ids = [])
            // {
            //     $user = User::find($userId);
            //     $ids[] = $userId; // Aggiungi l'ID dell'utente corrente

            //     foreach ($user->teamMembers as $member) {
            //         getTeamMemberIds($member->id, $ids); // Chiamata ricorsiva
            //     }

            //     return $ids;
            // }

            $teamMemberIds = array_values(array_unique($this->getTeamMemberIds((int)$id))); // deduplica // Ottieni gli ID di tutti i membri della squadra
            //Log::info('Team Member IDs: ' . implode(', ', $teamMemberIds));
            $contrattiUtente = Contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])->whereIn('inserito_da_user_id', $teamMemberIds)->orderBy('id', 'desc')->paginate($perPage);
        } elseif (Auth::user()->role_id == 5) {
            $macroProduct = contract_management::where('user_id', Auth::user()->id)->get();
            $macros = [];

            // Estrai tutti i macro_product_id in un array per ottimizzare la query successiva
            $macroProductIds = $macroProduct->pluck('macro_product_id')->toArray();

            // Recupera tutti i prodotti associati ai macro_product_id in una sola query
            $products = Product::whereIn('macro_product_id', $macroProductIds)->get();

            // Estrai tutti gli ID dei prodotti in un array
            foreach ($products as $product) {
                $macros[] = $product->id;
            }

            /* return response()->json(["body" => ["risposta" => $macros]]); */

            $contrattiUtente = Contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])
                ->whereIn('product_id', $macros)
                ->orderBy('id', 'desc')
                ->paginate($perPage);
        } elseif (Auth::user()->role_id == 3) {
            function getTeamMemberIds($userId, $ids = [])
            {
                $users = User::where('user_id_padre', $userId)->get();
                $ids[] = (int)$userId;
                foreach ($users as $user) {
                    $ids = array_merge(getTeamMemberIds($user->id, $ids));
                }
                return $ids;
            }

            $idLeadsUser = [];
            $teamMemberIds = getTeamMemberIds($id); // Ottieni gli ID di tutti i membri della squadra
            $cercaLeadUser = lead::where('invitato_da_user_id', Auth::user()->id)->get();
            foreach ($cercaLeadUser as $lead) {
                $idLeadsUser[] = $lead->id;
            }
            $idClientiConverted = [];
            $cercaConverted = leadConverted::whereIn('lead_id', $idLeadsUser)->get();
            foreach ($cercaConverted as $lc) {
                $idClientiConverted[] = $lc->cliente_id;
            }
            $contrattiUtente = Contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])->whereIn('associato_a_user_id', $teamMemberIds)->orderBy('id', 'desc')->paginate($perPage);

            //->whereHas('customer_data', function ($query) {  // Aggiungi whereHas
            //     $query->where('codice_fiscale', Auth::user()->codice_fiscale)->orWhere('partita_iva', Auth::user()->partita_iva);
            // })->where('associato_a_user_id', $teamMemberIds)
            //     ->get();


            // ->whereIn('associato_a_user_id', $teamMemberIds)->orWhereIn('associato_a_user_id', $idClientiConverted)->get();    

        }

        // Controlla se la query è stata eseguita correttamente e ha metodi di paginazione
        $hasPagination = is_object($contrattiUtente) && method_exists($contrattiUtente, 'currentPage');

        // Formattazione delle date - gestisce sia collection paginata che normale
        if ($hasPagination && $contrattiUtente->count() > 0) {
            $items = $contrattiUtente->getCollection();
        } elseif (is_object($contrattiUtente) && !$hasPagination) {
            $items = $contrattiUtente;
        } else {
            $items = collect(); // Collection vuota
        }

        foreach ($items as $contratto) {
            // Ottieni un oggetto Carbon per ogni data
            $data_inserimento = \Carbon\Carbon::parse($contratto->data_inserimento);
            $data_stipula = \Carbon\Carbon::parse($contratto->data_stipula);

            // // Formatta le date in italiano
            $contratto->data_inserimento = $data_inserimento->format('d/m/Y');
            $contratto->data_stipula = $data_stipula->format('d/m/Y');
        }

        // Gestione file storage
        $storageFilesByContract = [];
        if ($items->count() > 0) {
            foreach ($items as $contratto) {
                $optionStatus = option_status_contract::where('status_contract_id', $contratto->status_contract_id)->first();
                $storagePath = '/' . $contratto->id;
                $storageFiles = Storage::allFiles($storagePath);

                $storageFiles = array_map(function ($file) {
                    $split = explode('_', $file);
                    $split2 = explode('/', $split[0]);
                    $id = $split2[0];
                    $pathRemote = env('APP_URL');
                    return [

                        'name' => basename($file),
                        'basepath' => $pathRemote . '/storage/app/public/', // Estrai il nome del file dal percorso
                        'pathfull' => $pathRemote . '/storage/app/public/' . $file,
                        'id' => $id
                    ];
                }, $storageFiles);

                $storageFilesByContract[$contratto->id] = $storageFiles;
            }
        }

        // Return appropriato per tutti i ruoli con paginazione
        if ($hasPagination) {
            // Ha paginazione
            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => $contrattiUtente, // Solo i dati dei contratti
                    "pagination" => [
                        "current_page" => $contrattiUtente->currentPage(),
                        "last_page" => $contrattiUtente->lastPage(),
                        "per_page" => $contrattiUtente->perPage(),
                        "total" => $contrattiUtente->total(),
                        "from" => $contrattiUtente->firstItem(),
                        "to" => $contrattiUtente->lastItem(),
                        "next_page_url" => $contrattiUtente->nextPageUrl(),
                        "prev_page_url" => $contrattiUtente->previousPageUrl()
                    ],
                    "file" => $storageFilesByContract
                ]
            ]);
        } else {
            // Fallback per casi senza paginazione
            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => $contrattiUtente,
                    "file" => $storageFilesByContract
                ]
            ]);
        }
    }

    public function getContratto(Request $request, $id)
    {
        $contrattiUtente = Contract::with(['User', 'customer_data', 'status_contract', 'product', 'specific_data', 'payment_mode', 'backofficeNote', 'product.macro_product'])
            ->where('id', $id)
            ->get();
        $productIds = $contrattiUtente->pluck('product_id')->toArray(); // Ottieni tutti gli ID dei prodotti dai contratti
        $allProductForIds = product::where('id', $productIds)->get();
        $prodMacroId = $allProductForIds->pluck('macro_product_id');
        $altriProdotti = Product::where('macro_product_id', $prodMacroId)->get();
        $macro_prodotti = macro_product::with('product')->get();
        foreach ($contrattiUtente as $contratto) {
            // Assumiamo che le tue date siano in colonne chiamate 'data_inserimento' e 'data_stipula'
            $contratto->data_inserimento = Carbon::parse($contratto->data_inserimento)->format('d-m-Y');
            $contratto->data_stipula = Carbon::parse($contratto->data_stipula)->format('d-m-Y');
        }
        $storageFilesByContract = [];
        foreach ($contrattiUtente as $contratto) {
            $optionStatus = option_status_contract::where('status_contract_id', $contratto->status_contract_id)->first();
            $storagePath = '/' . $contratto->id; // Percorso di archiviazione dei file del contratto
            $storageFiles = Storage::allFiles($storagePath);
            if ($storageFiles) {
                # code...
                // Aggiungi il path e il nome del file all'array $storageFiles
                $storageFiles = array_map(function ($file) {
                    $split = explode('_', $file);
                    $split2 = explode('/', $split[0]);
                    $id = $split2[0];
                    $pathRemote = env('APP_URL');
                    return [
                        'name' => basename($file),
                        'basepath' => $pathRemote . '/storage/app/public/', // Estrai il nome del file dal percorso
                        'pathfull' => $pathRemote . '/storage/app/public/' . $file,
                        'id' => $id // URL completo del file
                    ];
                }, $storageFiles);

                $storageFilesByContract[$contratto->id] = $storageFiles;
            } else {
                $storageFilesByContract = "";
            }
        }

        return response()->json([
            "response" => "ok",
            "status" => "200",
            "body" => ["risposta" => $contrattiUtente, "file" => $storageFilesByContract, "option_status" => $optionStatus, "altri_prodotti" => $altriProdotti, "macro_prodotti" => $macro_prodotti]
        ]);
    }

    public function getPagamentoSystem()
    {

        $payment_mode = Payment_mode::all();
        $auth = Auth::user();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $payment_mode]]);
    }

    public function getStatiAvanzamento()
    {
        $auth = Auth::user();
        $statiAvanzamento = status_contract::all();
        $optionStatusContract = option_status_contract::with('status_contract', 'Role')->where('applicabile_da_role_id', "=", Auth::user()->role_id)->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => ["stati_avanzamento" => $statiAvanzamento, "status_option" => $optionStatusContract]]]);
    }

    public function getMacroStatiAvanzamento()
    {

        //$macroStatiAvanzamento = option_status_contract::with('status_contract')->get()->groupBy('macro_stato');
        //$macroStatiAvanzamento = option_status_contract::with('status_contract')->select('macro_stato','fase')->groupBy('macro_stato')->get();

        $macroStatiAvanzamento = option_status_contract::with('status_contract')->get(['macro_stato', 'fase', 'status_contract_id'])->groupBy(['macro_stato', 'status_contract.micro_stato']);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $macroStatiAvanzamento]]);
    }

    public function updateContratto(Request $request)
    {
        $statoContrattoOld = "";
        $statoContrattoNew = "";
        $contratto = Contract::with('status_contract')->where('id', $request->idContratto)->get();
        if ($contratto) {
            foreach ($contratto as $cont) {
                $idContraente = $cont->customer_data_id;
                $statoContrattoOld = $cont->status_contract->micro_stato;
            }
        }
        //return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $request->all()]]);
        if (is_numeric($request->stato_avanzamento)) {
            $updateContratto = Contract::where('id', $request->idContratto)->update(['status_contract_id' => $request->stato_avanzamento]);
            $contrattoNew = Contract::with('status_contract')->where('id', $request->idContratto)->get();
            if ($contrattoNew) {
                foreach ($contrattoNew as $newUpdate) {
                    $statoContrattoNew = $newUpdate->status_contract->micro_stato;
                }
            }
        }
        if ($request->note_backoffice) {
            $cercaNotaEsistente = backoffice_note::where('contract_id', $request->idContratto)->where('nota', $request->note_backoffice)->first();
            if (!$cercaNotaEsistente) {
                $inserimento_nota = backoffice_note::create([
                    "contract_id" => $request->idContratto,
                    "nota" => $request->note_backoffice,
                ]);
            }
        }

        //$findCustomer=customer_data::find('id',$contratto->customer_data_id);
        $contr = $request->nome_contraente;
        list($nome, $cognome) = explode(' ', $contr, 2);
        $findContraente = customer_data::where('id', $idContraente)->get();
        $updateProduct = contract::where('id', $request->idContratto)->update(['product_id' => $request->microprodotto, 'inserito_da_user_id' => $request->inserito_da]);
        foreach ($findContraente as $contraente) {
            if ($nome != $contraente->nome) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["nome" => $nome]);
            }
            if ($cognome != $contraente->cognome) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["cognome" => $cognome]);
            }
            if ($request->pivacodfisc_contraente != $contraente->codice_fiscale) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["codice_fiscale" => $request->pivacodfisc_contraente]);
            }
            if ($request->cap_contraente != $contraente->cap) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["cap" => $request->cap_contraente]);
            }
            if ($request->citta_contraente != $contraente->citta) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["citta" => $request->citta_contraente]);
            }
            if ($request->email_contraente != $contraente->email) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["email" => $request->email_contraente]);
            }
            if ($request->indirizzo_contraente != $contraente->indirizzo) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["indirizzo" => $request->indirizzo_contraente]);
            }
            if ($request->telefono_contraente != $contraente->telefono) {
                $updateContraente = customer_data::where('id', $idContraente)->update(["telefono" => $request->telefono_contraente]);
            }
        }

        // Gestione aggiornamento specific_data
        Log::info('Dati specifici ricevuti:', ['specific_data' => $request->specific_data]);
        if ($request->specific_data) {
            $specificDataArray = json_decode($request->specific_data, true);
            if ($specificDataArray && is_array($specificDataArray)) {
                // Raccogli gli ID delle righe che devono rimanere
                $idsToKeep = [];

                foreach ($specificDataArray as $item) {
                    if (isset($item['id']) && $item['id'] !== null) {
                        // Riga esistente: aggiorna
                        Specific_data::where('id', $item['id'])
                            ->where('contract_id', $request->idContratto)
                            ->update([
                                'domanda' => $item['domanda'],
                                'risposta_tipo_numero' => $item['risposta_tipo_numero'],
                                'risposta_tipo_stringa' => $item['risposta_tipo_stringa'],
                                'risposta_tipo_bool' => $item['risposta_tipo_bool'],
                                'tipo_risposta' => $item['tipo'],
                            ]);

                        $idsToKeep[] = $item['id'];
                    } else {
                        // Nuova riga: inserisci
                        $newRecord = Specific_data::create([
                            'domanda' => $item['domanda'],
                            'risposta_tipo_numero' => $item['risposta_tipo_numero'],
                            'risposta_tipo_stringa' => $item['risposta_tipo_stringa'],
                            'risposta_tipo_bool' => $item['risposta_tipo_bool'],
                            'tipo_risposta' => $item['tipo_risposta'],
                            'contract_id' => $request->idContratto,
                        ]);

                        $idsToKeep[] = $newRecord->id;
                    }
                }

                // Elimina le righe che non sono più presenti (se ci sono ID da mantenere)
                if (!empty($idsToKeep)) {
                    Specific_data::where('contract_id', $request->idContratto)
                        ->whereNotIn('id', $idsToKeep)
                        ->delete();
                }
            }
        }

        if ($statoContrattoNew != "") {
            $log = ModelsLog::create([
                'tipo_di_operazione' => "l'utente " . Auth::user()->name . " ha modificato lo stato di avanzamento del contratto con id " . $request->idContratto . " da " . $statoContrattoOld . " a " . $statoContrattoNew . " in data " . Carbon::now()->format("Y-m-d") . "",
                'datetime' => Carbon::now()->format("Y-m-d H:i:s"),
                'user_id' => Auth::user()->id,
            ]);
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => ["nome" => $nome, "cognome" => $cognome, "id" => $contratto, "old" => $contratto, "new" => $updateContratto]]]);
    }


    public function controlloProdottoNeiContratti($id)
    {

        $cercaProdotti = contract::where('product_id', $id)->exists();

        if ($cercaProdotti) {
            $trovato = 1;
        } else {
            $trovato = 0;
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $trovato]]);
    }

    public function updateStatoMassivoContratti(Request $request)
    {

        $contratti = json_decode($request->contratti);
        foreach ($contratti as $contratto) {
            $updateContratto = contract::find($contratto->id)->update(['status_contract_id' => $request->nuovostato]);
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $updateContratto]]);
    }

    public function contrattiPersonali($id)
    {



        function getTeamMemberIds($userId, $ids = [])
        {
            $users = User::where('user_id_padre', $userId)->get();
            $ids[] = (int)$userId;
            foreach ($users as $user) {
                $ids = array_merge(getTeamMemberIds($user->id, $ids));
            }
            return $ids;
        }

        $idLeadsUser = [];
        $teamMemberIds = getTeamMemberIds($id); // Ottieni gli ID di tutti i membri della squadra
        $cercaLeadUser = lead::where('invitato_da_user_id', Auth::user()->id)->get();
        foreach ($cercaLeadUser as $lead) {
            $idLeadsUser[] = $lead->id;
        }
        $idClientiConverted = [];
        $cercaConverted = leadConverted::whereIn('lead_id', $idLeadsUser)->get();
        foreach ($cercaConverted as $lc) {
            $idClientiConverted[] = $lc->cliente_id;
        }
        $contrattiUtente = Contract::with([
            'User',
            'UserSeu',
            'customer_data',
            'status_contract',
            'status_contract.option_status_contract',
            'product',
            'product.supplier',
            'product.macro_product',
            'specific_data',
            'payment_mode'
        ])->where('associato_a_user_id', $id)->get();

        // ->whereHas('customer_data', function ($query) {  // Aggiungi whereHas
        //     $query->where('codice_fiscale', Auth::user()->codice_fiscale)->orWhere('partita_iva', Auth::user()->partita_iva);
        // })->where('associato_a_user_id', $id)
        // ->get();



        foreach ($contrattiUtente as $contratto) {
            // Ottieni un oggetto Carbon per ogni data
            $data_inserimento = \Carbon\Carbon::parse($contratto->data_inserimento);
            $data_stipula = \Carbon\Carbon::parse($contratto->data_stipula);

            // // Formatta le date in italiano
            $contratto->data_inserimento = $data_inserimento->format('d/m/Y');
            $contratto->data_stipula = $data_stipula->format('d/m/Y');
        }



        if ($contrattiUtente) {
            $storageFilesByContract = [];
            foreach ($contrattiUtente as $contratto) {
                $storagePath = '/' . $contratto->id;
                $storageFiles = Storage::allFiles($storagePath);

                $storageFiles = array_map(function ($file) {
                    $split = explode('_', $file);
                    $split2 = explode('/', $split[0]);
                    $id = $split2[0];
                    $pathRemote = env('APP_URL');
                    return [

                        'name' => basename($file),
                        'basepath' => $pathRemote . '/storage/app/public/', // Estrai il nome del file dal percorso
                        'pathfull' => $pathRemote . '/storage/app/public/' . $file,
                        'id' => $id
                    ];
                }, $storageFiles);

                $storageFilesByContract[$contratto->id] = $storageFiles;
            }
        } else {
            $contrattiUtente = 0;
            $storageFilesByContract = 0;
        }

        // "id" => $id,

        return response()->json([
            "response" => "ok",
            "status" => "200",
            "body" => ["risposta" => $contrattiUtente, "file" => $storageFilesByContract]
        ]);
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
    public function show(contract $contract)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(contract $contract)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, contract $contract)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(contract $contract)
    {
        //
    }

    /**
     * Search contracts with server-side filtering
     */
    public function searchContratti(Request $request, $id)
    {
        Log::info('searchContratti chiamato', [
            'user_id' => $id,
            'request_params' => $request->all()
        ]);

        // Parametri di paginazione
        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);

        // Parametri di ordinamento
        $sortField = $request->get('sort_field', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validazione dei parametri
        $perPage = min($perPage, 100);
        $perPage = max($perPage, 10);

        // Validazione parametri di ordinamento
        $allowedSortFields = ['id', 'data_inserimento', 'data_stipula'];
        $allowedSortDirections = ['asc', 'desc'];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }

        if (!in_array($sortDirection, $allowedSortDirections)) {
            $sortDirection = 'desc';
        }

        // Ottieni i filtri dal frontend
        $filters = $request->get('filters', '');

        Log::info('Filtri ricevuti raw:', ['filters' => $filters]);

        // Decodifica i filtri se sono una stringa JSON
        $filterArray = [];
        if (!empty($filters)) {
            if (is_string($filters)) {
                // Prova a parsare come JSON
                $parsedFilters = json_decode($filters, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsedFilters)) {
                    $filterArray = $parsedFilters;
                }
            } elseif (is_array($filters)) {
                $filterArray = $filters;
            }
        }

        Log::info('Filtri parsati:', ['filterArray' => $filterArray]);

        // Converte l'array di filtri in un formato più gestibile
        $filterParams = [];
        foreach ($filterArray as $filter) {
            if (is_array($filter) && count($filter) >= 2) {
                $key = $filter[0];
                $value = $filter[1];

                // Gestisce i valori multipli (array)
                if (is_array($value)) {
                    $filterParams[$key] = $value;
                } else {
                    $filterParams[$key] = $value;
                }
            }
        }

        // Query base in base al ruolo utente
        if (Auth::user()->role_id == 1) {
            // Admin - può vedere tutti i contratti
            $query = contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode',
                'status_contract.option_status_contract'
            ]);
        } elseif (Auth::user()->role_id == 2 || Auth::user()->role_id == 4) {
            // Manager/Supervisor - può vedere i contratti del suo team
            $teamMemberIds = array_values(array_unique($this->getTeamMemberIds((int)$id))); // deduplica // Ottieni gli ID di tutti i membri della squadra


            $query = contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])->whereIn('inserito_da_user_id', $teamMemberIds);
        } elseif (Auth::user()->role_id == 5) {
            // Ruolo 5 - può vedere contratti solo per i macro prodotti assegnati
            $macroProduct = contract_management::where('user_id', Auth::user()->id)->get();
            $macros = [];

            // Estrai tutti i macro_product_id in un array per ottimizzare la query successiva
            $macroProductIds = $macroProduct->pluck('macro_product_id')->toArray();

            // Recupera tutti i prodotti associati ai macro_product_id in una sola query
            $products = Product::whereIn('macro_product_id', $macroProductIds)->get();

            // Estrai tutti gli ID dei prodotti in un array
            foreach ($products as $product) {
                $macros[] = $product->id;
            }

            $query = contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])->whereIn('product_id', $macros);
        } elseif (Auth::user()->role_id == 3) {
            // Ruolo 3 - gestione team e lead convertiti
            function getTeamMemberIds($userId, $ids = [])
            {
                $users = User::where('user_id_padre', $userId)->get();
                $ids[] = (int)$userId;
                foreach ($users as $user) {
                    $ids = array_merge(getTeamMemberIds($user->id, $ids));
                }
                return $ids;
            }

            $idLeadsUser = [];
            $teamMemberIds = getTeamMemberIds($id); // Ottieni gli ID di tutti i membri della squadra
            $cercaLeadUser = lead::where('invitato_da_user_id', Auth::user()->id)->get();
            foreach ($cercaLeadUser as $lead) {
                $idLeadsUser[] = $lead->id;
            }
            $idClientiConverted = [];
            $cercaConverted = leadConverted::whereIn('lead_id', $idLeadsUser)->get();
            foreach ($cercaConverted as $lc) {
                $idClientiConverted[] = $lc->cliente_id;
            }

            $query = contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])->whereIn('associato_a_user_id', $teamMemberIds);
        } else {
            // Altri ruoli - solo i propri contratti
            $query = contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode'
            ])->where('inserito_da_user_id', $id);
        }

        // Applica filtri specifici
        if (!empty($filterParams)) {
            if (isset($filterParams['ricerca'])) {
                $ricerca = $filterParams['ricerca'];
                if (is_string($ricerca) && !empty($ricerca)) {
                    $query->where(function ($searchQuery) use ($ricerca) {
                        $likeValue = "%{$ricerca}%";

                        $searchQuery->where('codice_contratto', 'like', $likeValue)
                            ->orWhereHas('customer_data', function ($customerQuery) use ($likeValue) {
                                $customerQuery->where('nome', 'like', $likeValue)
                                    ->orWhere('cognome', 'like', $likeValue)
                                    ->orWhere('ragione_sociale', 'like', $likeValue)
                                    ->orWhere('codice_fiscale', 'like', $likeValue)
                                    ->orWhere('partita_iva', 'like', $likeValue);
                            });

                        if (is_numeric($ricerca)) {
                            $searchQuery->orWhere('id', intval($ricerca));
                        }
                    });
                }
            }

            // Filtro per ID contratto (può essere singolo o multiplo)
            if (isset($filterParams['id'])) {
                $ids = $filterParams['id'];
                if (is_string($ids)) {
                    // Gestisce ID separati da virgola
                    $ids = array_map('trim', explode(',', $ids));
                    $ids = array_filter($ids, 'is_numeric');
                    $ids = array_map('intval', $ids);
                }
                if (is_array($ids) && !empty($ids)) {
                    $query->whereIn('id', $ids);
                }
            }

            // Filtro per nome cliente
            if (isset($filterParams['cliente'])) {
                $cliente = $filterParams['cliente'];
                if (is_string($cliente) && !empty($cliente)) {
                    $keywords = explode(' ', $cliente);
                    $query->whereHas('customer_data', function ($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $q->where(function ($q2) use ($keyword) {
                                $q2->where('nome', 'like', "%{$keyword}%")
                                    ->orWhere('cognome', 'like', "%{$keyword}%")
                                    ->orWhere('ragione_sociale', 'like', "%{$keyword}%");
                            });
                        }
                    });
                }
            }

            // Filtro per codice fiscale/partita IVA
            if (isset($filterParams['pivacf'])) {
                $pivacf = $filterParams['pivacf'];
                if (is_string($pivacf) && !empty($pivacf)) {
                    $query->whereHas('customer_data', function ($q) use ($pivacf) {
                        $q->where('codice_fiscale', 'like', "%{$pivacf}%")
                            ->orWhere('partita_iva', 'like', "%{$pivacf}%");
                    });
                }
            }

            if (isset($filterParams['codice_contratto'])) {
                $codiceContratto = $filterParams['codice_contratto'];
                if (is_string($codiceContratto) && !empty($codiceContratto)) {
                    $query->where('codice_contratto', 'like', "%{$codiceContratto}%");
                }
            }

            // Filtro per supplier
            if (isset($filterParams['supplier'])) {
                $supplier = $filterParams['supplier'];

                Log::info('Filtro supplier ricevuto:', ['supplier' => $supplier]);

                if (is_array($supplier)) {
                    // Multi-selezione: cerca contratti con uno qualsiasi dei supplier selezionati
                    Log::info('Supplier array detected:', $supplier);

                    $query->whereHas('product.supplier', function ($q) use ($supplier) {
                        $q->whereIn('nome_fornitore', $supplier);
                    });
                } elseif (is_string($supplier) && !empty($supplier)) {
                    // Singola selezione: cerca contratti con il supplier esatto
                    Log::info('Supplier string detected:', ['value' => $supplier]);

                    $query->whereHas('product.supplier', function ($q) use ($supplier) {
                        $q->where('nome_fornitore', $supplier);
                    });
                }
            }

            // Filtro per prodotto
            if (isset($filterParams['prodotto'])) {
                $prodotto = $filterParams['prodotto'];

                Log::info('Filtro prodotto ricevuto:', ['prodotto' => $prodotto]);

                if (is_array($prodotto)) {
                    // Multi-selezione: cerca contratti con uno qualsiasi dei prodotti selezionati
                    $query->whereHas('product', function ($q) use ($prodotto) {
                        $q->whereIn('descrizione', $prodotto);
                    });
                } elseif (is_string($prodotto) && !empty($prodotto)) {
                    // Singola selezione: cerca contratti con il prodotto esatto
                    $query->whereHas('product', function ($q) use ($prodotto) {
                        $q->where('descrizione', $prodotto);
                    });
                }
            }

            // Filtro per macro prodotto
            if (isset($filterParams['macroprodotto'])) {
                $macroprodotto = $filterParams['macroprodotto'];

                Log::info('Filtro macro prodotto ricevuto:', ['macroprodotto' => $macroprodotto]);

                if (is_array($macroprodotto)) {
                    // Multi-selezione: cerca contratti con uno qualsiasi dei macro prodotti selezionati
                    $query->whereHas('product.macro_product', function ($q) use ($macroprodotto) {
                        $q->whereIn('descrizione', $macroprodotto);
                    });
                } elseif (is_string($macroprodotto) && !empty($macroprodotto)) {
                    // Singola selezione: cerca contratti con il macro prodotto esatto
                    $query->whereHas('product.macro_product', function ($q) use ($macroprodotto) {
                        $q->where('descrizione', $macroprodotto);
                    });
                }
            }

            // Filtro per macro stato
            if (isset($filterParams['macrostato'])) {
                $macrostato = $filterParams['macrostato'];

                Log::info('Filtro macro stato ricevuto:', ['macrostato' => $macrostato]);

                if (is_array($macrostato)) {
                    // Multi-selezione: cerca contratti con uno qualsiasi dei macro stati selezionati
                    $query->whereHas('status_contract.option_status_contract', function ($q) use ($macrostato) {
                        $q->whereIn('macro_stato', $macrostato);
                    });
                } elseif (is_string($macrostato) && !empty($macrostato)) {
                    // Singola selezione: cerca contratti con il macro stato esatto
                    $query->whereHas('status_contract.option_status_contract', function ($q) use ($macrostato) {
                        $q->where('macro_stato', $macrostato);
                    });
                }
            }

            // Filtro per stato (micro stato)
            if (isset($filterParams['stato'])) {
                $stato = $filterParams['stato'];

                Log::info('Filtro stato ricevuto:', ['stato' => $stato]);

                if (is_array($stato)) {
                    // Multi-selezione: cerca contratti con uno qualsiasi degli stati selezionati
                    $query->whereHas('status_contract', function ($q) use ($stato) {
                        $q->whereIn('micro_stato', $stato);
                    });
                } elseif (is_string($stato) && !empty($stato)) {
                    // Singola selezione: cerca contratti con lo stato esatto
                    $query->whereHas('status_contract', function ($q) use ($stato) {
                        $q->where('micro_stato', $stato);
                    });
                }
            }

            // Filtro per SEU
            if (isset($filterParams['seu'])) {
                $seu = $filterParams['seu'];

                Log::info('Filtro SEU ricevuto:', ['seu' => $seu]);

                if (is_array($seu)) {
                    // Multi-selezione: cerca contratti con uno qualsiasi dei SEU selezionati
                    $query->whereHas('UserSeu', function ($q) use ($seu) {
                        $q->where(function ($subQ) use ($seu) {
                            foreach ($seu as $seuName) {
                                $subQ->orWhereRaw("CONCAT(cognome, ' ', name) = ?", [$seuName]);
                            }
                        });
                    });
                } elseif (is_string($seu) && !empty($seu)) {
                    // Singola selezione: cerca contratti con il SEU esatto
                    $query->whereHas('UserSeu', function ($q) use ($seu) {
                        $q->whereRaw("CONCAT(cognome, ' ', name) = ?", [$seu]);
                    });
                }
            }

            // Filtro per data inserimento
            if (isset($filterParams['datains'])) {
                $datains = $filterParams['datains'];

                Log::info('Filtro data inserimento ricevuto:', ['datains' => $datains]);

                if (is_array($datains) && count($datains) >= 2) {
                    $startDate = $datains[0];
                    $endDate = $datains[1];

                    // Converte le date dal formato dd/mm/yyyy al formato yyyy-mm-dd
                    try {
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d');
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d');

                        Log::info('Date inserimento convertite:', [
                            'start' => $startDate,
                            'end' => $endDate
                        ]);

                        $query->whereDate('data_inserimento', '>=', $startDate)
                            ->whereDate('data_inserimento', '<=', $endDate);
                    } catch (\Exception $e) {
                        Log::error('Errore conversione data inserimento:', [
                            'error' => $e->getMessage(),
                            'dates' => $datains
                        ]);
                    }
                }
            }

            // Filtro per data stipula
            if (isset($filterParams['datastipula'])) {
                $datastipula = $filterParams['datastipula'];

                Log::info('Filtro data stipula ricevuto:', ['datastipula' => $datastipula]);

                if (is_array($datastipula) && count($datastipula) >= 2) {
                    $startDate = $datastipula[0];
                    $endDate = $datastipula[1];

                    // Converte le date dal formato dd/mm/yyyy al formato yyyy-mm-dd
                    try {
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d');
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d');

                        Log::info('Date stipula convertite:', [
                            'start' => $startDate,
                            'end' => $endDate
                        ]);

                        $query->whereDate('data_stipula', '>=', $startDate)
                            ->whereDate('data_stipula', '<=', $endDate);
                    } catch (\Exception $e) {
                        Log::error('Errore conversione data stipula:', [
                            'error' => $e->getMessage(),
                            'dates' => $datastipula
                        ]);
                    }
                }
            }
        }

        // Applica l'ordinamento
        $query->orderBy($sortField, $sortDirection);

        // Log della query SQL prima dell'esecuzione
        Log::info('Query SQL generata:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection
        ]);

        // Esegui la query con paginazione
        $contrattiUtente = $query->paginate($perPage);

        // Log dei risultati
        Log::info('Risultati query:', [
            'total' => $contrattiUtente->total(),
            'count' => $contrattiUtente->count(),
            'current_page' => $contrattiUtente->currentPage()
        ]);

        // Formattazione delle date
        if (method_exists($contrattiUtente, 'getCollection')) {
            $items = $contrattiUtente->getCollection();
        } else {
            $items = $contrattiUtente;
        }

        foreach ($items as $contratto) {
            if ($contratto->data_inserimento) {
                $data_inserimento = \Carbon\Carbon::parse($contratto->data_inserimento);
                $contratto->data_inserimento = $data_inserimento->format('d/m/Y');
            }

            if ($contratto->data_stipula) {
                $data_stipula = \Carbon\Carbon::parse($contratto->data_stipula);
                $contratto->data_stipula = $data_stipula->format('d/m/Y');
            }
        }

        return response()->json([
            "response" => "ok",
            "status" => "200",
            "body" => [
                "risposta" => $contrattiUtente,
                "debug" => [
                    "filters_received" => $filters,
                    "parsed_filters" => $filterParams,
                    "query_executed" => true
                ]
            ]
        ]);
    }
}
