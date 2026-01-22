<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\lead;
use App\Models\Role;
use App\Models\User;
use App\Models\product;
use App\Models\contract;
use App\Models\supplier;
use App\Models\lead_status;
use Illuminate\Support\Str;
use App\Models\notification;
use App\Models\payment_mode;
use Hamcrest\Type\IsNumeric;
use Illuminate\Http\Request;
use App\Models\customer_data;
use App\Models\leadConverted;
use App\Models\macro_product;
use App\Models\qualification;
use App\Models\specific_data;
use App\Models\DetailQuestion;
use App\Models\backoffice_note;
use App\Models\status_contract;
use App\Models\supplier_category;
use Illuminate\Support\Facades\DB;
use App\Models\contract_management;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\option_status_contract;
use Illuminate\Support\Facades\Storage;
use App\Models\contract_type_information;
use App\Services\SystemLogService;


class ContractController extends Controller
{
    private const STATUS_REQUIRING_NOTIFICATION = [2, 3, 5, 7, 9, 11, 12, 14, 16];

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

        foreach ($optProdottoArray as $item) {
            // Estrai i dati dal nuovo formato strutturato
            $domanda = $item['domanda'];
            $tipo_risposta = $item['tipo_risposta'];

            // Usa i valori già mappati dal frontend
            $risposta_tipo_stringa = $item['risposta_tipo_stringa'];
            $risposta_tipo_numero = $item['risposta_tipo_numero'];
            $risposta_tipo_bool = $item['risposta_tipo_bool'];

            // Aggiungi anche le informazioni aggiuntive se servono
            $obbligatorio = isset($item['obbligatorio']) ? $item['obbligatorio'] : false;
            $opzioni_disponibili = isset($item['opzioni']) ? json_encode($item['opzioni']) : null;

            $specific_datas = Specific_data::create([
                'domanda' => $domanda,
                "risposta_tipo_numero" => $risposta_tipo_numero,
                "risposta_tipo_stringa" => $risposta_tipo_stringa,
                "risposta_tipo_bool" => $risposta_tipo_bool,
                "tipo_risposta" => $tipo_risposta,
                "contract_id" => $newContratto->id,
            ]);
        }

        // Log new contract creation
        SystemLogService::userActivity()->info('New contract created', [
            'contract_id' => $newContratto->id,
            'contract_code' => $newContratto->codice_contratto,
            'product_id' => $id_prodotto,
            'product_name' => $nome_prodotto,
            'client_id' => $idCliente,
            'contraente_id' => $id_contraente,
            'created_by_user_id' => $id_utente,
        ]);

        return response()->json(["response" => "ok", "status" => "200", "body" => ["id_Contratto" => $newContratto->id]]);
    }

    public function getContCodFPIva(Request $request)
    {

        $CFPI = $request->codFPIva;
        $tiporicerca = $request->tiporicerca;

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
            $contratto->data_inserimento = Carbon::parse($contratto->data_inserimento)->format('d-m-Y');
            $contratto->data_stipula = Carbon::parse($contratto->data_stipula)->format('d-m-Y');
        }

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
        return array_values(array_unique($ids));
    }

    /**
     * Helper method to get allowed product IDs for BackOffice/Operatore Web users
     * based on their assigned macro products in contract_managements table
     */
    private function getAllowedProductIdsForUser(int $userId): array
    {
        $macroProductIds = contract_management::where('user_id', $userId)
            ->pluck('macro_product_id')
            ->toArray();
        
        if (empty($macroProductIds)) {
            return [];
        }
        
        return Product::whereIn('macro_product_id', $macroProductIds)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if a user has permission to access a specific contract
     * Used for BackOffice (5) and Operatore Web (4) roles
     */
    private function userCanAccessContract(int $userId, int $contractId): bool
    {
        $contract = Contract::find($contractId);
        
        if (!$contract) {
            return false;
        }
        
        $product = Product::find($contract->product_id);
        
        if (!$product) {
            return false;
        }
        
        return contract_management::where('user_id', $userId)
            ->where('macro_product_id', $product->macro_product_id)
            ->exists();
    }

    public function getContratti(Request $request, $id)
    {
        // Parametri di paginazione comuni per tutti i ruoli
        $perPage = $request->get('per_page', 250);
        $authId = Auth::user()->id;
        $roleId = Auth::user()->role_id;

        // Admin (1): can see all contracts
        if ($roleId == 1) {
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
                'status_contract.option_status_contract',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ])->orderBy('id', 'desc')->paginate($perPage);
        } 
        // Advisor/SEU (2): can see contracts from their team
        elseif ($roleId == 2) {
            $teamMemberIds = array_values(array_unique($this->getTeamMemberIds((int)$id)));
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
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ])->whereIn('inserito_da_user_id', $teamMemberIds)->orderBy('id', 'desc')->paginate($perPage);
        } 
        // Cliente (3): can see contracts associated to them
        elseif ($roleId == 3) {
            function getTeamMemberIdsForCliente($userId, $ids = [])
            {
                $users = User::where('user_id_padre', $userId)->get();
                $ids[] = (int)$userId;
                foreach ($users as $user) {
                    $ids = array_merge(getTeamMemberIdsForCliente($user->id, $ids));
                }
                return $ids;
            }

            $idLeadsUser = [];
            $teamMemberIds = getTeamMemberIdsForCliente($id);
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
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ])->whereIn('associato_a_user_id', $teamMemberIds)->orderBy('id', 'desc')->paginate($perPage);
        }
        // Operatore Web (4) and BackOffice (5): can only see contracts for assigned macro products
        elseif (in_array($roleId, [4, 5])) {
            $allowedProductIds = $this->getAllowedProductIdsForUser($authId);

            $query = Contract::with([
                'User',
                'UserSeu',
                'customer_data',
                'status_contract',
                'status_contract.option_status_contract',
                'product',
                'product.supplier',
                'product.macro_product',
                'specific_data',
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ]);

            // Apply product filter only if there are allowed products
            if (!empty($allowedProductIds)) {
                $query->whereIn('product_id', $allowedProductIds);
            } else {
                // No products assigned = no contracts visible
                $query->whereRaw('1 = 0');
            }

            $contrattiUtente = $query->orderBy('id', 'desc')->paginate($perPage);
        }
        // Default fallback
        else {
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
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ])->where('inserito_da_user_id', $id)->orderBy('id', 'desc')->paginate($perPage);
        }

        // Controlla se la query è stata eseguita correttamente e ha metodi di paginazione
        $hasPagination = is_object($contrattiUtente) && method_exists($contrattiUtente, 'currentPage');

        // Formattazione delle date
        if ($hasPagination && $contrattiUtente->count() > 0) {
            $items = $contrattiUtente->getCollection();
        } elseif (is_object($contrattiUtente) && !$hasPagination) {
            $items = $contrattiUtente;
        } else {
            $items = collect();
        }

        foreach ($items as $contratto) {
            $data_inserimento = \Carbon\Carbon::parse($contratto->data_inserimento);
            $data_stipula = \Carbon\Carbon::parse($contratto->data_stipula);
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
                        'basepath' => $pathRemote . '/storage/app/public/',
                        'pathfull' => $pathRemote . '/storage/app/public/' . $file,
                        'id' => $id
                    ];
                }, $storageFiles);

                $storageFilesByContract[$contratto->id] = $storageFiles;
            }
        }

        // Return appropriato per tutti i ruoli con paginazione
        if ($hasPagination) {
            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => $contrattiUtente,
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
        $user = Auth::user();
        
        // For Operatore Web (4) and BackOffice (5) users, verify they have permission to view this contract
        if (in_array($user->role_id, [4, 5])) {
            if (!$this->userCanAccessContract($user->id, $id)) {
                return response()->json([
                    "response" => "error",
                    "status" => "403",
                    "body" => [
                        "risposta" => null, 
                        "messaggio" => "Non hai i permessi per visualizzare questo contratto"
                    ]
                ], 403);
            }
        }

        $contrattiUtente = Contract::with(['User', 'customer_data', 'status_contract', 'product', 'specific_data', 'payment_mode', 'backofficeNote', 'product.macro_product'])
            ->where('id', $id)
            ->get();
        $productIds = $contrattiUtente->pluck('product_id')->toArray();
        $allProductForIds = product::where('id', $productIds)->get();
        $prodMacroId = $allProductForIds->pluck('macro_product_id');
        $altriProdotti = Product::where('macro_product_id', $prodMacroId)->get();
        $macro_prodotti = macro_product::with('product')->get();
        foreach ($contrattiUtente as $contratto) {
            $contratto->data_inserimento = Carbon::parse($contratto->data_inserimento)->format('d-m-Y');
            $contratto->data_stipula = Carbon::parse($contratto->data_stipula)->format('d-m-Y');
        }
        $storageFilesByContract = [];
        foreach ($contrattiUtente as $contratto) {
            $optionStatus = option_status_contract::where('status_contract_id', $contratto->status_contract_id)->first();
            $storagePath = '/' . $contratto->id;
            $storageFiles = Storage::allFiles($storagePath);
            if ($storageFiles) {
                $storageFiles = array_map(function ($file) {
                    $split = explode('_', $file);
                    $split2 = explode('/', $split[0]);
                    $id = $split2[0];
                    $pathRemote = env('APP_URL');
                    return [
                        'name' => basename($file),
                        'basepath' => $pathRemote . '/storage/app/public/',
                        'pathfull' => $pathRemote . '/storage/app/public/' . $file,
                        'id' => $id
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
        
        // Check if user is authenticated
        if (!$auth) {
            return response()->json([
                "response" => "error",
                "status" => "401",
                "message" => "Unauthorized - Please login again"
            ], 401);
        }
        
        $statiAvanzamento = status_contract::all();
        $optionStatusContract = option_status_contract::with('status_contract', 'Role')
            ->where('applicabile_da_role_id', "=", $auth->role_id)
            ->get();
        
        return response()->json([
            "response" => "ok",
            "status" => "200",
            "body" => ["risposta" => ["stati_avanzamento" => $statiAvanzamento, "status_option" => $optionStatusContract]]
        ]);
    }
    
    public function getMacroStatiAvanzamento()
    {
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

        if (is_numeric($request->stato_avanzamento)) {
            $updateContratto = Contract::where('id', $request->idContratto)->update(['status_contract_id' => $request->stato_avanzamento]);
            $contrattoNew = Contract::with(['status_contract', 'User', 'UserSeu', 'customer_data'])->where('id', $request->idContratto)->first();
            
            $statoContrattoNew = $contrattoNew->status_contract->micro_stato;
            $mailSeu = $contrattoNew->UserSeu->email;
            
            // Check if status requires email and notification
            if (in_array((int)$request->stato_avanzamento, self::STATUS_REQUIRING_NOTIFICATION)) {
                // Send email
                Mail::to($mailSeu)->send(new \App\Mail\CambioStatoContratto($contrattoNew));
                
                // Send in-app notification to SEU
                $this->notifyContractStatusChange($contrattoNew, $statoContrattoOld, $statoContrattoNew);
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
        if ($request->specific_data) {
            $specificDataArray = json_decode($request->specific_data, true);
            if ($specificDataArray && is_array($specificDataArray)) {
                $idsToKeep = [];

                foreach ($specificDataArray as $item) {
                    if (isset($item['id']) && $item['id'] !== null) {
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

                if (!empty($idsToKeep)) {
                    Specific_data::where('contract_id', $request->idContratto)
                        ->whereNotIn('id', $idsToKeep)
                        ->delete();
                }
            }
        }

        // Log contract status change using new SystemLogService
        if ($statoContrattoNew != "") {
            SystemLogService::logContractStatusChange(
                $request->idContratto,
                $statoContrattoOld,
                $statoContrattoNew,
                Auth::user()->id
            );
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

    /**
     * Bulk update contract status with email notifications
     * 
     * Sends email notifications to SEU users when contracts are moved to specific statuses
     */
    public function updateStatoMassivoContratti(Request $request)
    {
        $contratti = json_decode($request->contratti);
        $nuovoStato = $request->nuovostato;
        
        $emailsSent = 0;
        $emailErrors = [];
        $notificationsSent = 0;
        
        foreach ($contratti as $contratto) {
            // Get old status before update
            $oldContract = Contract::with('status_contract')->find($contratto->id);
            $oldStatusLabel = $oldContract ? $oldContract->status_contract->micro_stato : 'N/A';
            
            // Update the contract status
            contract::find($contratto->id)->update(['status_contract_id' => $nuovoStato]);
            
            // If the new status requires email notification, send it
            if (in_array((int)$nuovoStato, self::STATUS_REQUIRING_NOTIFICATION)) {
                try {
                    // Load the contract with required relationships for the email
                    $contrattoCompleto = Contract::with(['status_contract', 'User', 'UserSeu', 'customer_data'])
                        ->where('id', $contratto->id)
                        ->first();
                    
                    if ($contrattoCompleto && $contrattoCompleto->UserSeu && $contrattoCompleto->UserSeu->email) {
                        $mailSeu = $contrattoCompleto->UserSeu->email;
                        
                        // Send email
                        Mail::to($mailSeu)->send(new \App\Mail\CambioStatoContratto($contrattoCompleto));
                        $emailsSent++;
                        
                        // Send in-app notification
                        $newStatusLabel = $contrattoCompleto->status_contract->micro_stato;
                        $this->notifyContractStatusChange($contrattoCompleto, $oldStatusLabel, $newStatusLabel);
                        $notificationsSent++;
                    }
                } catch (\Exception $e) {
                    // Log email error but continue processing other contracts
                    $emailErrors[] = [
                        'contract_id' => $contratto->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        // Log bulk status update
        $contractIds = array_map(function($c) { return $c->id; }, $contratti);
        SystemLogService::userActivity()->info('Bulk contract status update', [
            'contract_ids' => $contractIds,
            'new_status_id' => $nuovoStato,
            'contracts_count' => count($contratti),
            'emails_sent' => $emailsSent,
            'notifications_sent' => $notificationsSent,
            'email_errors' => count($emailErrors),
            'updated_by_user_id' => Auth::user()->id,
        ]);

        return response()->json([
            "response" => "ok", 
            "status" => "200", 
            "body" => [
                "risposta" => true,
                "contracts_updated" => count($contratti),
                "emails_sent" => $emailsSent,
                "notifications_sent" => $notificationsSent,
                "email_errors" => $emailErrors
            ]
        ]);
    }

    /**
     * Send in-app notification to SEU when contract status changes
     */
    private function notifyContractStatusChange(Contract $contract, string $oldStatus, string $newStatus): void
    {
        try {
            $currentUserId = Auth::id();
            $seuUserId = $contract->inserito_da_user_id;

            // Don't notify if same user made the change
            if ($currentUserId === $seuUserId) {
                return;
            }

            // Get customer name for notification
            $customerName = 'N/A';
            if ($contract->customer_data) {
                if ($contract->customer_data->nome && $contract->customer_data->cognome) {
                    $customerName = $contract->customer_data->nome . ' ' . $contract->customer_data->cognome;
                } elseif (!empty($contract->customer_data->ragione_sociale)) {
                    $customerName = $contract->customer_data->ragione_sociale;
                }
            }

            notification::create([
                'from_user_id' => $currentUserId,
                'to_user_id' => $seuUserId,
                'reparto' => 'contratto',
                'notifica' => "Contratto {$contract->codice_contratto} - Stato aggiornato a '{$newStatus}'",
                'visualizzato' => false,
                'notifica_html' => "<strong>Cambio stato contratto</strong><br>{$customerName}<br>Nuovo stato: {$newStatus}",
                'type' => notification::TYPE_CONTRACT_STATUS,
                'entity_type' => notification::ENTITY_CONTRACT,
                'entity_id' => $contract->id,
            ]);

            SystemLogService::userActivity()->info('Contract status notification sent', [
                'contract_id' => $contract->id,
                'contract_code' => $contract->codice_contratto,
                'seu_user_id' => $seuUserId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            SystemLogService::application()->error('Error sending contract status notification', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function contrattiPersonali($id)
    {
        function getTeamMemberIdsPersonali($userId, $ids = [])
        {
            $users = User::where('user_id_padre', $userId)->get();
            $ids[] = (int)$userId;
            foreach ($users as $user) {
                $ids = array_merge(getTeamMemberIdsPersonali($user->id, $ids));
            }
            return $ids;
        }

        $idLeadsUser = [];
        $teamMemberIds = getTeamMemberIdsPersonali($id);
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

        foreach ($contrattiUtente as $contratto) {
            $data_inserimento = \Carbon\Carbon::parse($contratto->data_inserimento);
            $data_stipula = \Carbon\Carbon::parse($contratto->data_stipula);
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
                        'basepath' => $pathRemote . '/storage/app/public/',
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

        // ID utente loggato per filtrare i messaggi dei ticket
        $authId = Auth::user()->id;
        $roleId = Auth::user()->role_id;

        // Ottieni i filtri dal frontend
        $filters = $request->get('filters', '');

        // Decodifica i filtri se sono una stringa JSON
        $filterArray = [];
        if (!empty($filters)) {
            if (is_string($filters)) {
                $parsedFilters = json_decode($filters, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsedFilters)) {
                    $filterArray = $parsedFilters;
                }
            } elseif (is_array($filters)) {
                $filterArray = $filters;
            }
        }

        // Converte l'array di filtri in un formato più gestibile
        $filterParams = [];
        foreach ($filterArray as $filter) {
            if (is_array($filter) && count($filter) >= 2) {
                $key = $filter[0];
                $value = $filter[1];

                if (is_array($value)) {
                    $filterParams[$key] = $value;
                } else {
                    $filterParams[$key] = $value;
                }
            }
        }

        // Query base in base al ruolo utente
        // Admin (1): can see all contracts
        if ($roleId == 1) {
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
                'status_contract.option_status_contract',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ]);
        } 
        // Advisor/SEU (2): can see contracts from their team
        elseif ($roleId == 2) {
            $teamMemberIds = array_values(array_unique($this->getTeamMemberIds((int)$id)));

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
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ])->whereIn('inserito_da_user_id', $teamMemberIds);
        } 
        // Cliente (3): can see contracts associated to them
        elseif ($roleId == 3) {
            function getTeamMemberIdsSearch($userId, $ids = [])
            {
                $users = User::where('user_id_padre', $userId)->get();
                $ids[] = (int)$userId;
                foreach ($users as $user) {
                    $ids = array_merge(getTeamMemberIdsSearch($user->id, $ids));
                }
                return $ids;
            }

            $idLeadsUser = [];
            $teamMemberIds = getTeamMemberIdsSearch($id);
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
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ])->whereIn('associato_a_user_id', $teamMemberIds);
        } 
        // Operatore Web (4) and BackOffice (5): can only see contracts for assigned macro products
        elseif (in_array($roleId, [4, 5])) {
            $allowedProductIds = $this->getAllowedProductIdsForUser($authId);

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
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ]);

            // Apply product filter
            if (!empty($allowedProductIds)) {
                $query->whereIn('product_id', $allowedProductIds);
            } else {
                // No products assigned = no contracts visible
                $query->whereRaw('1 = 0');
            }
        } 
        // Default fallback: user sees only their own contracts
        else {
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
                'payment_mode',
                'ticket' => function ($q) use ($authId) {
                    $q->whereNotIn('status', ['deleted', 'closed'])->with([
                        'messages' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId)
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                        }
                    ])->withCount([
                        'messages as unread_messages_count' => function ($mq) use ($authId) {
                            $mq->where('user_id', '!=', $authId);
                        }
                    ]);
                },
            ])->where('inserito_da_user_id', $id);
        }

        if (!empty($filterParams)) {
            if (isset($filterParams['ricerca'])) {
                $ricerca = $filterParams['ricerca'];
                if (is_string($ricerca) && !empty($ricerca)) {
                    $trimmedRicerca = trim($ricerca);
                    
                    if (is_numeric($trimmedRicerca)) {
                        $numericId = intval($trimmedRicerca);
                        
                        $query->where(function ($searchQuery) use ($numericId, $trimmedRicerca) {
                            $searchQuery->where('id', $numericId)
                                ->orWhere('codice_contratto', 'like', "%{$trimmedRicerca}%");
                        });
                    } else {
                        $query->where(function ($searchQuery) use ($trimmedRicerca) {
                            $likeValue = "%{$trimmedRicerca}%";

                            $searchQuery->where('codice_contratto', 'like', $likeValue)
                                ->orWhereHas('customer_data', function ($customerQuery) use ($likeValue) {
                                    $customerQuery->where('nome', 'like', $likeValue)
                                        ->orWhere('cognome', 'like', $likeValue)
                                        ->orWhere('ragione_sociale', 'like', $likeValue);
                                });
                        });
                    }
                }
            }

            // Filtro per ID contratto
            if (isset($filterParams['id'])) {
                $ids = $filterParams['id'];
                if (is_string($ids)) {
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

                if (is_array($supplier)) {
                    $query->whereHas('product.supplier', function ($q) use ($supplier) {
                        $q->whereIn('nome_fornitore', $supplier);
                    });
                } elseif (is_string($supplier) && !empty($supplier)) {
                    $query->whereHas('product.supplier', function ($q) use ($supplier) {
                        $q->where('nome_fornitore', $supplier);
                    });
                }
            }

            // Filtro per prodotto
            if (isset($filterParams['prodotto'])) {
                $prodotto = $filterParams['prodotto'];

                if (is_array($prodotto)) {
                    $query->whereHas('product', function ($q) use ($prodotto) {
                        $q->whereIn('descrizione', $prodotto);
                    });
                } elseif (is_string($prodotto) && !empty($prodotto)) {
                    $query->whereHas('product', function ($q) use ($prodotto) {
                        $q->where('descrizione', $prodotto);
                    });
                }
            }

            // Filtro per macro prodotto
            if (isset($filterParams['macroprodotto'])) {
                $macroprodotto = $filterParams['macroprodotto'];

                if (is_array($macroprodotto)) {
                    $query->whereHas('product.macro_product', function ($q) use ($macroprodotto) {
                        $q->whereIn('descrizione', $macroprodotto);
                    });
                } elseif (is_string($macroprodotto) && !empty($macroprodotto)) {
                    $query->whereHas('product.macro_product', function ($q) use ($macroprodotto) {
                        $q->where('descrizione', $macroprodotto);
                    });
                }
            }

            // Filtro per macro stato
            if (isset($filterParams['macrostato'])) {
                $macrostato = $filterParams['macrostato'];

                if (is_array($macrostato)) {
                    $query->whereHas('status_contract.option_status_contract', function ($q) use ($macrostato) {
                        $q->whereIn('macro_stato', $macrostato);
                    });
                } elseif (is_string($macrostato) && !empty($macrostato)) {
                    $query->whereHas('status_contract.option_status_contract', function ($q) use ($macrostato) {
                        $q->where('macro_stato', $macrostato);
                    });
                }
            }

            // Filtro per stato (micro stato)
            if (isset($filterParams['stato'])) {
                $stato = $filterParams['stato'];

                if (is_array($stato)) {
                    $query->whereHas('status_contract', function ($q) use ($stato) {
                        $q->whereIn('micro_stato', $stato);
                    });
                } elseif (is_string($stato) && !empty($stato)) {
                    $query->whereHas('status_contract', function ($q) use ($stato) {
                        $q->where('micro_stato', $stato);
                    });
                }
            }

            // Filtro per SEU
            if (isset($filterParams['seu'])) {
                $seu = $filterParams['seu'];

                if (is_array($seu)) {
                    $query->whereHas('UserSeu', function ($q) use ($seu) {
                        $q->where(function ($subQ) use ($seu) {
                            foreach ($seu as $seuName) {
                                $subQ->orWhereRaw("CONCAT(cognome, ' ', name) = ?", [$seuName]);
                            }
                        });
                    });
                } elseif (is_string($seu) && !empty($seu)) {
                    $query->whereHas('UserSeu', function ($q) use ($seu) {
                        $q->whereRaw("CONCAT(cognome, ' ', name) = ?", [$seu]);
                    });
                }
            }

            // Filtro per data inserimento
            if (isset($filterParams['datains'])) {
                $datains = $filterParams['datains'];

                if (is_array($datains) && count($datains) >= 2) {
                    $startDate = $datains[0];
                    $endDate = $datains[1];

                    try {
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d');
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d');

                        $query->whereDate('data_inserimento', '>=', $startDate)
                            ->whereDate('data_inserimento', '<=', $endDate);
                    } catch (\Exception $e) {
                        // Date format error - skip filter
                    }
                }
            }

            // Filtro per data stipula
            if (isset($filterParams['datastipula'])) {
                $datastipula = $filterParams['datastipula'];

                if (is_array($datastipula) && count($datastipula) >= 2) {
                    $startDate = $datastipula[0];
                    $endDate = $datastipula[1];

                    try {
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d');
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d');

                        $query->whereDate('data_stipula', '>=', $startDate)
                            ->whereDate('data_stipula', '<=', $endDate);
                    } catch (\Exception $e) {
                        // Date format error - skip filter
                    }
                }
            }
        }

        // Applica l'ordinamento
        $query->orderBy($sortField, $sortDirection);

        // Esegui la query con paginazione
        $contrattiUtente = $query->paginate($perPage);

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