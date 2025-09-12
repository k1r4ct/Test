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
use Illuminate\Support\Facades\Log;
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
            'lead_status_id' => 2, // Imposta lo stato iniziale del lead
            'assegnato_a' => $validated['userId'], // Assegna il lead all'utente che lo ha creato
            'consenso' => $validated['privacy'] ? 1 : 0, // Consenso per la privacy
        ]);

        } catch (\Exception $e) {
            Log::error('Errore durante la creazione del lead: ' . $e->getMessage());
            return response()->json(["response" => "ok", "status" => "500", "body" =>'Errore durante la creazione del lead. Riprova più tardi.']);
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

    public function nuovoClienteLead(Request $request)
    {
        //return response()->json(["response" => "ok", "status" => "200", "body" => ["richiesta"=>$request->all()]]);
        $nome = $cognome = $codice_fiscale = $partita_iva = $ragione_sociale = "";

        if (request('tipo') == "consumer") {
            $nome = request('nome');
            $cognome = request('cognome');
            $codice_fiscale = request('codice_fiscale');
            $controlloEsistente = User::where('codice_fiscale', $codice_fiscale)->get();
            $ragione_sociale = null;
            $partita_iva = null;
        }

        if (request('tipo') == "businness") {
            $ragione_sociale = request('ragione_sociale');
            $partita_iva = request('partita_iva');
            $controlloEsistente = User::where('partita_iva', $partita_iva)->get();
            $nome = null;
            $cognome = null;
            $codice_fiscale = null;
        }

        $email = request('email');
        $telefono = request('telefono');
        $indirizzo = request('indirizzo');
        $provincia = request('provincia');
        $citta = request('citta');
        $nazione = request('nazione');
        $cap = request('cap');
        $qualifica = request('qualifica');
        $ruolo = request('ruolo');
        $user_padre = request('us_padre');
        $password = request('password');
        if ($controlloEsistente->isEmpty()) {
            $utente = User::create([
                "name" => $nome,
                "cognome" => $cognome,
                "ragione_sociale" => $ragione_sociale,
                "email" => $email,
                "telefono" => $telefono,
                "codice_fiscale" => $codice_fiscale,
                "partita_iva" => $partita_iva,
                "indirizzo" => $indirizzo,
                "provincia" => $provincia,
                "citta" => $citta,
                "nazione" => $nazione,
                "cap" => $cap,
                "qualification_id" => $qualifica,
                "role_id" => $ruolo,
                "user_id_padre" => $user_padre,
                "password" => $password,
            ]);

            $nuovaConversione = leadConverted::create([
                "lead_id" => $request->id_lead,
                "cliente_id" => $utente->id,
            ]);

            return response()->json(["response" => "ok", "status" => "200", "body" => ["id" => $utente->id, "tipo" => request('tipo')]]);
        } else {
            return response()->json(["response" => "ko", "body" => "Utente gia esistente"]);
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
            'leadConverted'
        ])->where(function ($q) use ($userIds) {
            $q->whereIn('invitato_da_user_id', $userIds)
                ->orWhereIn('assegnato_a', $userIds);
        })->get();

        foreach ($getLeads as $leadrow) {
            $data_creazione = \Carbon\Carbon::parse($leadrow->created_at);
            // sovrescrivi nel campo created_at la data formattata
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

    public function updateAssegnazioneLead(Request $request)
    {

        /* $updateAssegnazione = lead::find($request->id_lead);
        $updateAssegnazione->update(['assegnato_a' => $request->id_user]);
        if (Auth::User()->id != $request->id_user) {
            $notifica = notification::create([
                'from_user_id' => Auth::User()->id,
                'to_user_id' => $request->id_user,
                'reparto' => 'Leads',
                'notifica' => "l'utente " . Auth::User()->name . " ti ha assegnato un nuovo lead id: " . $updateAssegnazione->id . " Lead nome e cognome: " . $updateAssegnazione->nome . " " . $updateAssegnazione->cognome . "",
                'notifica_html' => "l'utente <b style='color:#6d9ebc'>" . Auth::User()->name . "</b> ti ha assegnato un nuovo lead id: <b style='color:#6d9ebc'>" . $updateAssegnazione->id . "</b> Lead nome e cognome: <b style='color:#6d9ebc'>" . $updateAssegnazione->nome . " " . $updateAssegnazione->cognome . "</b>",
            ]);
            return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $notifica]]);
        } */
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $request->all()]]);
    }

    public function getLeadsDayClicked(Request $request)
    {

        $getLeads = lead::with('leadstatus', 'User')->whereIn('id', $request->all())->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $getLeads]]);
    }

    public function getAppointments()
    {

        $appuntamenti = lead::whereNotNull('data_appuntamento') // Aggiungi questa condizione
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
}
