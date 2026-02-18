<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\lead;
use App\Models\Role;
use App\Models\User;
use App\Mail\LeadMail;
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
use App\Models\contract_management;
use App\Http\Controllers\Controller;
use App\Mail\LeadMailInvitante;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\option_status_contract;
use Illuminate\Support\Facades\Storage;
use App\Models\contract_type_information;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    public function storeLeadExternal(Request $request)
    {
        // Validazione dei dati in ingresso
        $validated = $request->validate([
            'userId' => 'required|string',
            'nome' => 'required|string',
            'cognome' => 'required|string',
            'telefono' => 'required|string',
            'email' => 'required|email',
            'privacy' => 'required|boolean',
        ]);

        // Creazione del nuovo lead
        try {
            $lead = lead::create([
                'invitato_da_user_id' => $validated['userId'],
                'nome' => $validated['nome'],
                'cognome' => $validated['cognome'],
                'telefono' => $validated['telefono'],
                'email' => $validated['email'],
                'lead_status_id' => 2,
                'assegnato_a' => $validated['userId'],
                'consenso' => $validated['privacy'] ? 1 : 0,
            ]);

        } catch (\Exception $e) {
            return response()->json(["response" => "ko", "status" => "500", "body" => 'Errore durante la creazione del lead. Riprova più tardi.']);
        }

        return response()->json(["response" => "ok", "status" => "200", "body" => $lead]);
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
    public function show(lead $lead)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(lead $lead)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, lead $lead)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(lead $lead)
    {
        //
    }

    /**
     * Convert a lead into a new client user.
     * 
     * Validation rules:
     * - Lead must exist
     * - Lead must be in 'Lead OK' status (lead_statuses where micro_stato = 'Lead OK')
     * - Lead must not already be converted
     * - User with same codice_fiscale/partita_iva must not exist
     * 
     * After conversion:
     * - Creates the new user
     * - Creates lead_converteds record with converted_by_user_id tracking
     */
    public function nuovoClienteLead(Request $request)
    {
        try {
            // Verify lead exists
            $leadId = $request->input('id_lead');
            $leadRecord = lead::with('leadstatus')->find($leadId);

            if (!$leadRecord) {
                return response()->json([
                    "response" => "ko",
                    "status" => "404",
                    "body" => ["error" => "Lead non trovato"]
                ], 404);
            }

            // Check if lead is already converted
            $alreadyConverted = leadConverted::where('lead_id', $leadId)->exists();
            if ($alreadyConverted) {
                return response()->json([
                    "response" => "ko",
                    "status" => "400",
                    "body" => ["error" => "Questo lead è già stato convertito in cliente"]
                ], 400);
            }

            // Check if lead is in 'Lead OK' status
            $leadOkStatusIds = lead_status::where('micro_stato', 'Lead OK')->pluck('id')->toArray();
            if (!in_array($leadRecord->lead_status_id, $leadOkStatusIds)) {
                $currentStatus = $leadRecord->leadstatus ? $leadRecord->leadstatus->micro_stato : 'Sconosciuto';
                return response()->json([
                    "response" => "ko",
                    "status" => "400",
                    "body" => [
                        "error" => "Il lead deve essere in stato 'Lead OK' per essere convertito. Stato attuale: " . $currentStatus
                    ]
                ], 400);
            }

            // Prepare user data based on type
            $nome = $cognome = $codice_fiscale = $partita_iva = $ragione_sociale = "";

            if (request('tipo') == "consumer") {
                $nome = request('nome');
                $cognome = request('cognome');
                $codice_fiscale = request('codice_fiscale');
                $controlloEsistente = User::where('codice_fiscale', $codice_fiscale)->get();
                $ragione_sociale = null;
                $partita_iva = null;
            }

            if (request('tipo') == "business") {
                $ragione_sociale = request('ragione_sociale');
                $partita_iva = request('partita_iva');
                $controlloEsistente = User::where('partita_iva', $partita_iva)->get();
                $nome = null;
                $cognome = null;
                $codice_fiscale = null;
            }

            if ($controlloEsistente->isEmpty()) {
                $utente = User::create([
                    "name" => $nome,
                    "cognome" => $cognome,
                    "ragione_sociale" => $ragione_sociale,
                    "email" => request('email'),
                    "telefono" => request('telefono'),
                    "codice_fiscale" => $codice_fiscale,
                    "partita_iva" => $partita_iva,
                    "indirizzo" => request('indirizzo'),
                    "provincia" => request('provincia'),
                    "citta" => request('citta'),
                    "nazione" => request('nazione'),
                    "cap" => request('cap'),
                    "qualification_id" => request('qualifica'),
                    "role_id" => request('ruolo'),
                    "user_id_padre" => request('us_padre'),
                    "password" => request('password'),
                ]);

                // Create conversion record with who performed it
                $nuovaConversione = leadConverted::create([
                    "lead_id" => $leadId,
                    "cliente_id" => $utente->id,
                    "converted_by_user_id" => Auth::id(),
                ]);

                return response()->json([
                    "response" => "ok",
                    "status" => "200",
                    "body" => ["id" => $utente->id, "tipo" => request('tipo')]
                ]);
            } else {
                return response()->json(["response" => "ko", "body" => "Utente gia esistente"]);
            }

        } catch (\Exception $e) {
            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }

    public function storeNewLead(Request $request)
    {
        if ($request->consenso) {
            $consenso = 1;
        } else {
            $consenso = 0;
        }
        $newLeads = lead::create([
            'invitato_da_user_id' => Auth::user()->id,
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'lead_status_id' => 2,
            'assegnato_a' => Auth::user()->id,
            'consenso' => $consenso,
        ]);
        $datiUtentiMail = [
            "Invitante" => Auth::user()->name . " " . Auth::user()->cognome,
            "amico" => $request->nome . " " . $request->cognome,
            "email_cliente_sc" => Auth::user()->email,
            "telefono_cliente_sc" => Auth::user()->telefono,
        ];
        Mail::to($request->email)->send(new LeadMail($datiUtentiMail));
        Mail::to(Auth::user()->email)->send(new LeadMailInvitante($datiUtentiMail));
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $newLeads]]);
    }

    public function getLeads()
    {
        $level = env('GLOBAL_LEVEL');
        $userId = Auth::user()->id;

        // Ottieni gli ID dei sottoposti dell'utente loggato (ricorsivamente)
        $teamMemberIds = User::where('user_id_padre', $userId)->pluck('id')->toArray();
        $allTeamMemberIds = $teamMemberIds;
        
        function getSubordinateIds($parentId, &$allTeamMemberIds)
        {
            $subordinateIds = User::where('user_id_padre', $parentId)->pluck('id')->toArray();
            $allTeamMemberIds = array_merge($allTeamMemberIds, $subordinateIds);
            foreach ($subordinateIds as $memberId) {
                getSubordinateIds($memberId, $allTeamMemberIds);
            }
        }

        getSubordinateIds($userId, $allTeamMemberIds);

        // Crea un array con l'ID dell'utente loggato e gli ID di tutti i suoi sottoposti
        $userIds = array_merge([$userId], $allTeamMemberIds);
        
        // Ottieni i lead 
        $getLeads = Lead::with([
            'leadstatus' => function ($query) {
                $query->with('Colors');
            },
            'User',
            'invitedBy',
            'leadConverted'
        ])->where(function ($q) use ($userIds) {
            $q->whereIn('invitato_da_user_id', $userIds)
                ->orWhereIn('assegnato_a', $userIds);
        })->get();

        foreach ($getLeads as $leadrow) {
            $data_creazione = \Carbon\Carbon::parse($leadrow->created_at);
            $leadrow->setAttribute('data_inserimento', $data_creazione->format('d/m/Y'));
        }

        $contaleads = count($getLeads);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $getLeads, "Totale_Leads" => $contaleads, "level" => $level]]);
    }

    public function getUserForLeads()
    {
        $userForLeads = user::whereIn('role_id', [1, 2, 4, 5])->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $userForLeads]]);
    }

    /**
     * Update lead assignment to a different SEU/BO/Admin.
     * Called from the lead detail modal when changing "Assegnato a" dropdown.
     */
    public function updateAssegnazioneLead(Request $request)
    {
        try {
            $leadId = $request->input('id_lead');
            $newUserId = $request->input('id_user');

            if (!$leadId || !$newUserId) {
                return response()->json([
                    "response" => "ko",
                    "status" => "400",
                    "body" => ["error" => "id_lead e id_user sono obbligatori"]
                ], 400);
            }

            // Verify the target user exists
            $targetUser = User::find($newUserId);
            if (!$targetUser) {
                return response()->json([
                    "response" => "ko",
                    "status" => "404",
                    "body" => ["error" => "Utente non trovato"]
                ], 404);
            }

            // Verify the lead exists
            $leadRecord = lead::find($leadId);
            if (!$leadRecord) {
                return response()->json([
                    "response" => "ko",
                    "status" => "404",
                    "body" => ["error" => "Lead non trovato"]
                ], 404);
            }

            // Update the lead assignment
            $leadRecord->update(['assegnato_a' => $newUserId]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => "Lead assegnato a " . $targetUser->name . " " . $targetUser->cognome,
                    "lead_id" => $leadId,
                    "assegnato_a" => $newUserId,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }

    public function getLeadsDayClicked(Request $request)
    {
        $getLeads = lead::with('leadstatus', 'User')->whereIn('id', $request->all())->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $getLeads]]);
    }

    public function getAppointments()
    {
        $appuntamenti = lead::whereNotNull('data_appuntamento')
            ->where(function ($query) {
                $query->where('invitato_da_user_id', Auth::user()->id)
                    ->orWhere('assegnato_a', Auth::user()->id);
            })->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $appuntamenti]]);
    }

    public function updateLead(Request $request)
    {
        $updateLead = lead::where('id', $request->idLead)->update(['data_appuntamento' => $request->newDate]);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $request->all()]]);
    }

    public function getStatiLeads()
    {
        $leadStatus = lead_status::where('applicabile_da_role_id', Auth::user()->role_id)->select('micro_stato', 'id')->get()->unique('micro_stato');
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $leadStatus]]);
    }

    public function appuntamentoLead(Request $request)
    {
        if (isset($request->data_appuntamento) && isset($request->ora_appuntamento)) {
            $updateLeadAppointment = lead::where('id', $request->id_lead)->update(['data_appuntamento' => $request->data_appuntamento, 'ora_appuntamento' => $request->ora_appuntamento, 'lead_status_id' => $request->stato_id]);
        } else {
            $updateLeadAppointment = lead::where('id', $request->id_lead)->update(['lead_status_id' => $request->stato_id, 'data_appuntamento' => NULL]);
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $request->all()]]);
    }

    public function getColorRowStatusLead($id)
    {
        $color = lead_status::with('Colors')->where('id', $id)->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $color]]);
    }

    /**
     * Check if an email or phone number already exists in the leads table.
     * Used when BO creates a new user from the Clienti section to warn them
     * if a matching lead exists that should be converted from the Leads page instead.
     * 
     * GET /api/checkLeadMatch?email=xxx&telefono=xxx
     */
    public function checkLeadMatch(Request $request)
    {
        $email = $request->input('email');
        $telefono = $request->input('telefono');

        if (!$email && !$telefono) {
            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["matches" => [], "has_match" => false]
            ]);
        }

        $query = lead::with(['leadstatus', 'invitedBy', 'leadConverted']);

        $query->where(function ($q) use ($email, $telefono) {
            if ($email) {
                $q->where('email', $email);
            }
            if ($telefono) {
                if ($email) {
                    $q->orWhere('telefono', $telefono);
                } else {
                    $q->where('telefono', $telefono);
                }
            }
        });

        $matches = $query->get()->map(function ($leadRecord) {
            return [
                'lead_id' => $leadRecord->id,
                'nome' => $leadRecord->nome,
                'cognome' => $leadRecord->cognome,
                'email' => $leadRecord->email,
                'telefono' => $leadRecord->telefono,
                'stato' => $leadRecord->leadstatus ? $leadRecord->leadstatus->micro_stato : 'Sconosciuto',
                'invitato_da' => $leadRecord->invitedBy
                    ? $leadRecord->invitedBy->name . ' ' . $leadRecord->invitedBy->cognome
                    : null,
                'invitato_da_user_id' => $leadRecord->invitato_da_user_id,
                'is_converted' => $leadRecord->is_converted,
                'data_inserimento' => $leadRecord->created_at ? $leadRecord->created_at->format('d/m/Y') : null,
            ];
        });

        return response()->json([
            "response" => "ok",
            "status" => "200",
            "body" => [
                "matches" => $matches,
                "has_match" => $matches->count() > 0,
                "match_count" => $matches->count(),
            ]
        ]);
    }
}