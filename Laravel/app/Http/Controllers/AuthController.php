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

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(){
        try {
            $credentials = request(['email', 'password']);

            // Verifica delle credenziali (aggiunto per sicurezza)
            if (empty($credentials['email']) || empty($credentials['password'])) {
                return response()->json(['error' => 'Email and password are required.'], 400);
            }

            // Ottieni l'utente (ottimizzato con first())
            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $token = auth()->login($user);

            // Ritorna il token e i dati utente in un array più leggibile
            return response()->json([
                'token' => $this->respondWithToken($token),
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(){
        try {
            $userId = Auth::user()->id;

            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $user = User::with('Role', 'qualification')
                ->where('email', auth()->user()->email)
                ->first();

            // Ottieni tutti i membri del team (ricorsivamente)
            $team = $this->getTeamMembers($userId);

            $storagePath = 'userImage/' . $userId . '/immagineUser/';
            $storagepathDefault = 'userImage/default/';

            // Ottieni tutte le immagini caricate
            $images = Storage::disk('public')->allFiles($storagePath);

            if ($images && count($images) > 0) {
                // Ordina le immagini in base alla data di modifica (dalla più recente alla più vecchia)
                usort($images, function ($a, $b) {
                    return Storage::disk('public')->lastModified($b) <=> Storage::disk('public')->lastModified($a);
                });

                // Genera l'URL corretto per la prima immagine (la più recente)
                $path = asset('storage/' . $images[0]);
            } else {
                // Se non ci sono immagini caricate, prendi la prima di default
                $defaultImages = Storage::disk('public')->allFiles($storagepathDefault);
                if ($defaultImages && count($defaultImages) > 0) {
                    $path = asset('storage/' . $defaultImages[0]);
                } else {
                    $path = "";
                }
            }

            $contratti = contract::where('inserito_da_user_id', $userId)->count();
            return response()->json([
                "user" => $user, 
                "immagine" => $path,
                "team" => $team,
                "numero_contratti" => $contratti
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    // Funzione ricorsiva per ottenere i membri del team
    private function getTeamMembers($userId) {
        // Ottieni i membri del team che hanno l'utente corrente come padre
        $teamMembers = User::with('Role', 'qualification')->where('user_id_padre', $userId)->where('role_id',2)->get();

        foreach ($teamMembers as $member) {
            $path="";
            $storagePath = '/userImage/' . $member->id;
            $storagepathDefault = "/userImage/default/";
            $storageFiles = Storage::allFiles($storagePath);
            $storageFilesDefault = Storage::allFiles($storagepathDefault);
            if ($storageFiles) {
                foreach ($storageFiles as $file) {
                    $path = $file;
                }
            } else {
                foreach ($storageFilesDefault as $file) {
                    $path = $file;
                }
            }
            // Verifica che il membro non sia uguale al suo stesso padre per evitare ricorsione infinita
            if ($member->id !== $userId) {
                // Chiama la funzione ricorsivamente per ottenere i sottoposti di questo membro
                $member->children = $this->getTeamMembers($member->id);
                $member->foto=$path;
            } else {
                $member->children = [];
                $member->foto=""; // Nessun subordinato se l'utente è se stesso
            }
        }

        return $teamMembers;
    }

    public function codFPIva(Request $request){

        $codFPIva = request('codFPIva');
        $tipoRichiesta = request('tiporicerca');
        if ($tipoRichiesta == "CODICE FISCALE") {
            $result = User::where('codice_fiscale', "=", $codFPIva)->Where('codice_fiscale', "!=", "")->get();
            $tipo = "consumer";
            $result2 = customer_data::where('codice_fiscale', "=", $codFPIva)->Where('codice_fiscale', "!=", "")->get();
        } else if ($tipoRichiesta == "PARTITA IVA") {
            $result = User::Where('partita_iva', "=", $codFPIva)->Where('partita_iva', "!=", "")->get();
            $tipo = "businness";
            $result2 = customer_data::where('partita_iva', "=", $codFPIva)->Where('codice_fiscale', "!=", "")->get();
        }

        $id = "null";
        $id2 = "null";
        $trovatoCliente = false;
        $trovatoContraente = false;

        if (!$result->isEmpty()) {
            foreach ($result as $key) {
                $id = $key->id;
            }
            $trovatoCliente = true;
        }

        if (!$result2->isEmpty()) {
            foreach ($result2 as $key) {
                $id2 = $key->id;
            }
            $trovatoContraente = true;
        }

        if ($trovatoCliente || ($trovatoCliente && $trovatoContraente)) {
            return response()->json(["response" => "ok", "status" => "200", "body" => ["id" => $id, "tipo" => $tipo], "contraente" => ["id" => $id2]]);
        } elseif (!$trovatoCliente && $trovatoContraente) {
            return response()->json(["response" => "ok", "status" => "200", "body" => ["id" => $id, "tipo" => $tipo], "contraente" => ["id" => $id2]]);
        } else {
            return response()->json(["response" => "ko"]);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(){
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(){
        return $this->respondWithToken(auth()->refresh());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function nuovoCliente(Request $request){

        $nome = $cognome = $codice_fiscale = $partita_iva = $ragione_sociale = "";

        if (request('tipo') == "consumer") {
            $nome = request('nome');
            $cognome = request('cognome');
            $codice_fiscale = request('codice_fiscale');
            $controlloEsistente = User::where('codice_fiscale', $codice_fiscale)->get();
            $ragione_sociale = null;
            $partita_iva = null;
            $password= request('password');
        }

        if (request('tipo') == "businness") {
            $ragione_sociale = request('ragione_sociale');
            $partita_iva = request('partita_iva');
            $controlloEsistente = User::where('partita_iva', $partita_iva)->get();
            $nome = null;
            $cognome = null;
            $codice_fiscale = null;
            $password= request('password');
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
            return response()->json(["response" => "ok", "status" => "200", "body" => ["id" => $utente->id, "tipo" => request('tipo')]]);
        } else {
            return response()->json(["response" => "ko", "body" => "Utente gia esistente"]);
        }

        //return response()->json(["nome" => $nome, "cognome" => $cognome, "email" => $email, "codice_fiscale" => $codice_fiscale, "indirizzo" => $indirizzo, "citta" => $citta, "nazione" => $nazione, "cap" => $cap, "qualifica" => $qualifica, "ruolo" => $ruolo,"User_padre"=>$user_padre]);
        // return response()->json($controlloEsistente);
        //return response()->json(["response" => "ok", "status" => "200", "body" => ["id" => $utente->id, "tipo" => request('tipo')]]);
    }
    public function copiautente($id){

        $user = User::where('id', $id)->get();

        return response(["response" => "ok", "status" => "200", "body" => $user]);
    }
    public function storeIMG(Request $request){
        Log::debug($request->all());
        $request->validate([
            'file' => 'required|mimes:jpeg,png,jpg,gif,pdf|max:10240',
            //'idContratto' => 'required|integer|exists:App\Models\contract,id',
            // Assumiamo che tu abbia un modello Contratto
        ]);
        $idUser = Auth::user()->id;
        // Ottieni il file
        $file = $request->file('file');
        if (str_contains($request->nameFile, "_")) {
            $carattereDaSostituire = "_";
            $nuovoCarattere = "-";
            $nameFile = str_replace($carattereDaSostituire, $nuovoCarattere, $request->nameFile);
        } else {
            $nameFile = $request->nameFile;
        }
        $data = Carbon::now()->format('Y-m-d-H-i-s');
        if ($request->idContratto && ($request->idContratto != null || $request->idContratto != NULL || $request->idContratto != "")) {
            $idContratto = $request->idContratto;
            $imageName = $idContratto . "_" . $data . "_" . $nameFile;
            $path = $file->storeAs('/' . $request->idContratto . '/', $imageName);
        } else {
            $imageName = $idUser . "_" . $data . "_" . $nameFile;
            $path = $file->storeAs('/userImage/' . $idUser . '/immagineUser/', $imageName);
        }
        // Genera un nome univoco per l'immagine (per evitare sovrascritture)
        //$imageName = uniqid() . '.' . $file->getClientOriginalExtension();
        // Salva l'immagine nel filesystem (ad esempio, in storage/app/public/images)

        // Salva i dati nel database
        /* Image::create([
            'nome_file' => $imageName,
            'percorso' => $path,
            'contratto_id' => $request->input('idContratto'), // Associa l'immagine al contratto
        ]); */
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $imageName]]);
    }
    public function uploadProfileImage(Request $request){
        Log::debug($request->all());
        $idUser = Auth::user()->id;
        $request->validate([
            'file' => 'required|mimes:jpeg,png,jpg,gif,pdf|max:2048',
            //'idContratto' => 'required|integer|exists:App\Models\contract,id',
            // Assumiamo che tu abbia un modello Contratto
        ]);

        // Ottieni il file
        $file = $request->file('file');

        // Genera un nome univoco per l'immagine (per evitare sovrascritture)
        //$imageName = uniqid() . '.' . $file->getClientOriginalExtension();
        $data = Carbon::now()->format('Y-m-d-H-i-s');
        $imageName = $idUser . "_" . $data . "_" . $request->nameFile;
        // Salva l'immagine nel filesystem (ad esempio, in storage/app/public/images)
        $path = $file->storeAs('/' . $request->idUser . '/userProfileImage/', $imageName);

        // Salva i dati nel database
        /* Image::create([
            'nome_file' => $imageName,
            'percorso' => $path,
            'contratto_id' => $request->input('idContratto'), // Associa l'immagine al contratto
        ]); */
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $imageName]]);
    }
    public function deleteIMG(Request $request){
        if (isset($request->nameFileGet)) {
            $file = $request->nameFileGet;
            $nameFile = $file;
        } else {
            $file = $request->nameFile;
            if (str_contains($file, "_")) {
                $carattereDaSostituire = "_";
                $nuovoCarattere = "-";
                $nameFile = str_replace($carattereDaSostituire, $nuovoCarattere, $file);
            }
        }

        $idContratto = $request->idContratto;
        $storagePath = '/' . $request->idContratto;
        $storageFiles = Storage::allFiles($storagePath);
        if ($storageFiles) {
            foreach ($storageFiles as $key) {
                $split = explode("/", $key);
                $filename = $split[1]; // Ottieni direttamente il nome del file

                if ($filename === $nameFile) { // Confronta il nome del file completo
                    Storage::delete('/' . $idContratto . '/' . $filename);
                    break; // Esci dal ciclo dopo aver eliminato il file
                }
            }
        }
        $contafile = count(Storage::allFiles('/' . $idContratto . '/'));
        if ($contafile == 0) {
            Storage::deleteDirectory('/' . $idContratto . '/');
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $file, "file" => $nameFile, "contafile" => $contafile]]);
    }
    public function attesaCaricamentoImmagini() {}

    public function getFiles($id){
        $storagePath = '/' . $id; // Percorso di archiviazione dei file del contratto
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

            $storageFilesByContract[$id] = $storageFiles;
        } else {
            $storageFilesByContract = "";
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $storageFilesByContract]]);
    }

    public function getMessageNotification(){
        $message = notification::where('to_user_id', Auth::User()->id)->where('visualizzato', "!=", 1)->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $message]]);
    }

    public function markReadMessage(notification $message, $id){

        $messageUpdate = notification::find($id);
        $messageUpdate->update(["visualizzato" => 1]);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $messageUpdate]]);
    }

    public function getAllUser(user $user){
        $allUser = user::with('Role', 'qualification','contract_management')->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $allUser]]);
    }

    public function updatePassw(Request $request){
        $updatePassw=user::find($request->idUser);
        if (Hash::check($request->oldPw,$updatePassw->password)) {
            $updatePassw->update(['password'=>Hash::make($request->newPw)]);
            return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" =>"Password Modificata"]]);
        }else {
            return response()->json(["response" => "ko", "status" => "201", "body" => ["risposta" =>"La password vecchaia è errata"]]);
        }

    }

    public function dettagliUtente($id){
        $utente=User::with('Role', 'qualification','contract_management')->where('id',$id)->get();
        $macro_prodotti=macro_product::all();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" =>$utente,"macro_product"=>$macro_prodotti]]);
    }

    public function updateUtente(Request $request){

        Log::info('Aggiornamento utente: ' . json_encode($request->all()));
        $updateUser=User::find($request->idUtente);
        Log::info('Utente trovato: ' . json_encode($updateUser));
        //return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" =>"Utente modificato ! " ]]);

        if ($updateUser->name!=$request->nomeutente) {
            $updateUser->update(['name'=>$request->nomeutente]);
        }

        if ($updateUser->cognome!=$request->cognomeUtente) {
            $updateUser->update(['cognome'=>$request->cognomeUtente]);
        }

        if (isset($request->cod_fPivaUtente) && strlen($request->cod_fPivaUtente)>11) {
            if ($updateUser->codice_fiscale!=$request->cod_fPivaUtente) {
                $updateUser->update(['codice_fiscale'=>$request->cod_fPivaUtente]);
            }
        }else {
            if ($updateUser->partita_iva!=$request->cod_fPivaUtente) {
                $updateUser->update(['partita_iva'=>$request->cod_fPivaUtente]);
            }
        }

        if ($updateUser->email!=$request->emailUtente) {
            $updateUser->update(['email'=>$request->emailUtente]);
        }

        if ($updateUser->ragione_sociale!=$request->ragione_soc) {
            $updateUser->update(['ragione_sociale'=>$request->ragione_soc]);
        }

        if (isset($request->ruolo) && $updateUser->role_id!=$request->ruolo) {
            $updateUser->update(['role_id'=>$request->ruolo]);
        }

        if (isset($request->qualifica) && $updateUser->qualification_id!=$request->qualifica) {
            $updateUser->update(['qualification_id'=>$request->qualifica]);
        }

        if (isset($request->cod_Utente) && $updateUser->codice!=$request->cod_Utente) {
            $updateUser->update(['codice'=>$request->cod_Utente]);
        }

        if (isset($request->seu) && $updateUser->user_id_padre!=$request->seu) {
            $updateUser->update(['user_id_padre'=>$request->seu]);
        }

        if ($request->resetpwd == "true" || $request->resetpwd == 1) {
            $updateUser->update(['password'=>'$2y$10$8fzMmLsdHiSm.70tmlkBN.f8e6LtDnnTLJ8t61MK/ak3MrA.eeo3W']);
        }

        if (isset($request->activeUser)) {
            $updateUser->update(['stato_user'=>$request->activeUser]);
        }

        if (isset($request->contract_management)) {
            $exist = [];
            $requestIds = array_column($request->contract_management, 'id'); // Ottieni gli ID dalla richiesta

            foreach ($request->contract_management as $key) {
                $id = $key['id'];
                $findCM = contract_management::where('user_id', $request->idUtente)->get();
                $associazionePresente = false;

                foreach ($findCM as $value) {
                    if ($value->macro_product_id == $id) {
                        $exist[$value->macro_product_id] = "Gia esistente";
                        $associazionePresente = true;
                        break;
                    }
                }

                if (!$associazionePresente) {
                    $exist[$id] = "inserita associazione";
                    contract_management::create([
                        'user_id' => $request->idUtente,
                        'macro_product_id' => $id
                    ]);
                }
            }

            // Rimozione delle associazioni non più presenti
            $dbIds = contract_management::where('user_id', $request->idUtente)->pluck('macro_product_id')->toArray();
            $idsDaRimuovere = array_diff($dbIds, $requestIds); // Trova gli ID da rimuovere

            if (!empty($idsDaRimuovere)) {
                contract_management::where('user_id', $request->idUtente)
                                 ->whereIn('macro_product_id', $idsDaRimuovere)
                                 ->delete();
            }
        }

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" =>"Utente modificato ! " . $updateUser]]);

    }

    public function recuperaSEU(){

        //$seu=User::where('role_id',2)->get();
        Log::info("utente loggato : Ruolo=>" . Auth::user()->role_id);
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 5) {
            $seu=User::where('role_id', 2)->orWhere('role_id', 5)->get();
        }else{
            $userId = Auth::user()->id;
            //Log::info('Utente non Admin, ID utente: ' . $userId);
            //return response()->json(["response" => "ok", "status" => "200", "body" => [" risp. pers. " =>$userId]]);
            // Funzione ricorsiva per estrarre tutti gli ID da una struttura annidata di utenti
            function extractIds($users)
            {
                $ids = [];
                foreach ($users as $user) {
                    $ids[] = $user->id;
                    if (isset($user->children) && !empty($user->children)) {
                        $ids = array_merge($ids, extractIds($user->children));
                    }
                }
                return $ids;
            }

            $seuSub = $this->getTeamMembers($userId);
            // Log::info('Sottoposti trovati: ' . json_encode($seuSub));
            $ids = extractIds($seuSub);
            // Log::info('IDs estratti: ' . json_encode($ids));
            $seu = User::whereIn('id', $ids)->where('role_id', 2)->get();
        }

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" =>$seu]]);

    }
}
