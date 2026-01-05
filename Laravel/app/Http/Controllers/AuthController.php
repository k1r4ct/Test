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
use App\Services\SystemLogService;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('auth:api', ['except' => ['login','logout']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(){
        try {
            $credentials = request(['email', 'password']);

            // Validate required fields
            if (empty($credentials['email']) || empty($credentials['password'])) {
                SystemLogService::auth()->warning('Login attempt with missing credentials', [
                    'email_provided' => !empty($credentials['email']),
                    'password_provided' => !empty($credentials['password']),
                ]);
                return response()->json(['error' => 'Email and password are required.'], 400);
            }

            // Find the user
            $user = User::where('email', $credentials['email'])->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                SystemLogService::auth()->warning('Failed login attempt', [
                    'email' => $credentials['email'],
                    'user_exists' => $user !== null,
                    'reason' => $user ? 'invalid_password' : 'user_not_found',
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Generate token
            $token = auth()->login($user);

            // Log successful login
            SystemLogService::auth()->info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => trim(($user->name ?? '') . ' ' . ($user->cognome ?? '')),
                'role_id' => $user->role_id,
            ]);

            // Return token and user data
            return response()->json([
                'token' => $this->respondWithToken($token),
                'user' => $user
            ]);

        } catch (\Exception $e) {
            SystemLogService::auth()->error('Login error', [
                'email' => $credentials['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ], $e);
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

            // Get all team members (recursively)
            $team = $this->getTeamMembers($userId);

            $storagePath = 'userImage/' . $userId . '/immagineUser/';
            $storagepathDefault = 'userImage/default/';

            // Get all uploaded images
            $images = Storage::disk('public')->allFiles($storagePath);

            if ($images && count($images) > 0) {
                // Sort images by modification date (most recent first)
                usort($images, function ($a, $b) {
                    return Storage::disk('public')->lastModified($b) <=> Storage::disk('public')->lastModified($a);
                });
                // Generate correct URL for the first image (most recent)
                $path = asset('storage/' . $images[0]);
            } else {
                // If no uploaded images, get default
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

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(){
        try {
            $user = Auth::user();
            
            if ($user) {
                SystemLogService::auth()->info('User logged out', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => trim(($user->name ?? '') . ' ' . ($user->cognome ?? '')),
                ]);
                
                auth()->logout();
            } else {
                SystemLogService::auth()->info('Logout called without valid session');
            }

            return response()->json(['message' => 'Successfully logged out']);

        } catch (\Exception $e) {
            SystemLogService::auth()->error('Logout error', [
                'error' => $e->getMessage(),
            ], $e);
            return response()->json(['error' => 'Logout failed'], 500);
        }
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
     * @return array
     */
    protected function respondWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Recursive function to get team members
     */
    private function getTeamMembers($userId) {
        // Get team members who have the current user as parent
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
            // Check that member is not the same as parent to avoid infinite recursion
            if ($member->id !== $userId) {
                // Call function recursively to get subordinates of this member
                $member->children = $this->getTeamMembers($member->id);
                $member->foto=$path;
            } else {
                $member->children = [];
                $member->foto=""; // No subordinates if user is themselves
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
            $tipo = "business";
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

        $message = [];
        if ($trovatoCliente) {
            $message[] = ["Cliente" => $id, "esito" => "Cliente presente in database"];
        }
        if ($trovatoContraente) {
            $message[] = ["Contraente" => $id2, "esito" => "Contraente presente in database"];
        }
        if (!$trovatoCliente && !$trovatoContraente) {
            $message[] = ["esito" => "Nessun dato in archivio"];
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $message, "cod_cf_piva" => $codFPIva, "tipo_utente" => $tipo, "trovato_cliente" => $trovatoCliente, "trovato_contraente" => $trovatoContraente, "id_cliente" => $id, "id_contraente" => $id2]]);
    }

    public function nuovoCliente(){
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

            // Log new client creation
            SystemLogService::userActivity()->info('New client created', [
                'created_user_id' => $utente->id,
                'tipo' => request('tipo'),
                'email' => $email,
                'created_by_user_id' => Auth::id(),
            ]);

            return response()->json(["response" => "ok", "status" => "200", "body" => ["id" => $utente->id, "tipo" => request('tipo')]]);
        } else {
            return response()->json(["response" => "ko", "body" => "Utente gia esistente"]);
        }
    }

    /**
     * Copy user data.
     * 
     * GET /api/copiautente{id}
     */
    public function copiautente($id){
        $user = User::where('id', $id)->get();
        return response(["response" => "ok", "status" => "200", "body" => $user]);
    }

    // ==================== FILE MANAGEMENT METHODS ====================

    /**
     * Store uploaded image/document for contracts or user profile.
     * 
     * POST /api/storeIMG
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeIMG(Request $request){
        $request->validate([
            'file' => 'required|mimes:jpeg,png,jpg,gif,pdf|max:10240',
        ]);

        $idUser = Auth::user()->id;

        // Get the file
        $file = $request->file('file');

        // Sanitize filename - replace underscores with dashes
        if (str_contains($request->nameFile, "_")) {
            $nameFile = str_replace("_", "-", $request->nameFile);
        } else {
            $nameFile = $request->nameFile;
        }

        $data = Carbon::now()->format('Y-m-d-H-i-s');

        if ($request->idContratto && ($request->idContratto != null || $request->idContratto != NULL || $request->idContratto != "")) {
            // Store file for contract
            $idContratto = $request->idContratto;
            $imageName = $idContratto . "_" . $data . "_" . $nameFile;
            $path = $file->storeAs('/' . $request->idContratto . '/', $imageName);

            // Log contract file upload
            SystemLogService::userActivity()->info('Contract file uploaded', [
                'contract_id' => $idContratto,
                'file_name' => $imageName,
                'original_name' => $request->nameFile,
                'uploaded_by_user_id' => $idUser,
            ]);
        } else {
            // Store file for user profile
            $imageName = $idUser . "_" . $data . "_" . $nameFile;
            $path = $file->storeAs('/userImage/' . $idUser . '/immagineUser/', $imageName);

            // Log user profile image upload
            SystemLogService::userActivity()->info('User profile image uploaded', [
                'file_name' => $imageName,
                'original_name' => $request->nameFile,
                'uploaded_by_user_id' => $idUser,
            ]);
        }

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $imageName]]);
    }

    /**
     * Upload profile image for user.
     * 
     * POST /api/immagineProfiloUtente
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfileImage(Request $request){
        $idUser = Auth::user()->id;

        $request->validate([
            'file' => 'required|mimes:jpeg,png,jpg,gif,pdf|max:2048',
        ]);

        // Get the file
        $file = $request->file('file');

        $data = Carbon::now()->format('Y-m-d-H-i-s');
        $imageName = $idUser . "_" . $data . "_" . $request->nameFile;

        // Store the image
        $path = $file->storeAs('/' . $request->idUser . '/userProfileImage/', $imageName);

        // Log profile image upload
        SystemLogService::userActivity()->info('User profile image uploaded via uploadProfileImage', [
            'file_name' => $imageName,
            'original_name' => $request->nameFile,
            'target_user_id' => $request->idUser,
            'uploaded_by_user_id' => $idUser,
        ]);

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $imageName]]);
    }

    /**
     * Delete uploaded image/document.
     * 
     * POST /api/deleteIMG
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteIMG(Request $request){
        if (isset($request->nameFileGet)) {
            $file = $request->nameFileGet;
            $nameFile = $file;
        } else {
            $file = $request->nameFile;
            if (str_contains($file, "_")) {
                $nameFile = str_replace("_", "-", $file);
            } else {
                $nameFile = $file;
            }
        }

        $idContratto = $request->idContratto;
        $storagePath = '/' . $request->idContratto;
        $storageFiles = Storage::allFiles($storagePath);

        if ($storageFiles) {
            foreach ($storageFiles as $key) {
                $split = explode("/", $key);
                $filename = $split[1]; // Get the file name directly

                if ($filename === $nameFile) { // Compare full file name
                    Storage::delete('/' . $idContratto . '/' . $filename);

                    // Log file deletion
                    SystemLogService::userActivity()->info('Contract file deleted', [
                        'contract_id' => $idContratto,
                        'file_name' => $filename,
                        'deleted_by_user_id' => Auth::id(),
                    ]);

                    break; // Exit loop after deleting the file
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

    /**
     * Get all files for a contract.
     * 
     * GET /api/getFiles{id}
     * 
     * @param int $id Contract ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFiles($id){
        $storagePath = '/' . $id;
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

            $storageFilesByContract[$id] = $storageFiles;
        } else {
            $storageFilesByContract = "";
        }
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $storageFilesByContract]]);
    }

    // ==================== NOTIFICATION METHODS ====================

    public function getMessageNotification(){
        $message = notification::where('to_user_id', Auth::User()->id)->where('visualizzato', "!=", 1)->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $message]]);
    }

    public function markReadMessage(notification $message, $id){
        $messageUpdate = notification::find($id);
        $messageUpdate->update(["visualizzato" => 1]);
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $messageUpdate]]);
    }

    // ==================== USER MANAGEMENT METHODS ====================

    public function getAllUser(user $user){
        $allUser = user::with('Role', 'qualification','contract_management')->get();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $allUser]]);
    }

    public function updatePassw(Request $request){
        $updatePassw = user::find($request->idUser);
        
        if (Hash::check($request->oldPw, $updatePassw->password)) {
            $updatePassw->update(['password' => Hash::make($request->newPw)]);
            
            // Log password change
            SystemLogService::auth()->info('Password changed successfully', [
                'user_id' => $request->idUser,
                'email' => $updatePassw->email,
                'changed_by_user_id' => Auth::id(),
            ]);
            
            return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => "Password Modificata"]]);
        } else {
            // Log failed password change attempt
            SystemLogService::auth()->warning('Failed password change attempt - wrong old password', [
                'user_id' => $request->idUser,
                'email' => $updatePassw->email,
                'attempted_by_user_id' => Auth::id(),
            ]);
            
            return response()->json(["response" => "ko", "status" => "201", "body" => ["risposta" => "La password vecchaia è errata"]]);
        }
    }

    public function dettagliUtente($id){
        $utente = User::with('Role', 'qualification', 'contract_management')->where('id', $id)->get();
        $macro_prodotti = macro_product::all();
        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $utente, "macro_product" => $macro_prodotti]]);
    }

    public function updateUtente(Request $request){
        $updateUser = User::find($request->idUtente);
        $changes = [];

        if ($updateUser->name != $request->nomeutente) {
            $changes['name'] = ['old' => $updateUser->name, 'new' => $request->nomeutente];
            $updateUser->update(['name' => $request->nomeutente]);
        }

        if ($updateUser->cognome != $request->cognomeUtente) {
            $changes['cognome'] = ['old' => $updateUser->cognome, 'new' => $request->cognomeUtente];
            $updateUser->update(['cognome' => $request->cognomeUtente]);
        }

        if (isset($request->cod_fPivaUtente) && strlen($request->cod_fPivaUtente) > 11) {
            if ($updateUser->codice_fiscale != $request->cod_fPivaUtente) {
                $changes['codice_fiscale'] = ['old' => $updateUser->codice_fiscale, 'new' => $request->cod_fPivaUtente];
                $updateUser->update(['codice_fiscale' => $request->cod_fPivaUtente]);
            }
        } else {
            if ($updateUser->partita_iva != $request->cod_fPivaUtente) {
                $changes['partita_iva'] = ['old' => $updateUser->partita_iva, 'new' => $request->cod_fPivaUtente];
                $updateUser->update(['partita_iva' => $request->cod_fPivaUtente]);
            }
        }

        if ($updateUser->email != $request->emailUtente) {
            $changes['email'] = ['old' => $updateUser->email, 'new' => $request->emailUtente];
            $updateUser->update(['email' => $request->emailUtente]);
        }

        if ($updateUser->ragione_sociale != $request->ragione_soc) {
            $changes['ragione_sociale'] = ['old' => $updateUser->ragione_sociale, 'new' => $request->ragione_soc];
            $updateUser->update(['ragione_sociale' => $request->ragione_soc]);
        }

        if (isset($request->ruolo) && $updateUser->role_id != $request->ruolo) {
            $changes['role_id'] = ['old' => $updateUser->role_id, 'new' => $request->ruolo];
            $updateUser->update(['role_id' => $request->ruolo]);
        }

        if (isset($request->qualifica) && $updateUser->qualification_id != $request->qualifica) {
            $changes['qualification_id'] = ['old' => $updateUser->qualification_id, 'new' => $request->qualifica];
            $updateUser->update(['qualification_id' => $request->qualifica]);
        }

        if (isset($request->cod_Utente) && $updateUser->codice != $request->cod_Utente) {
            $changes['codice'] = ['old' => $updateUser->codice, 'new' => $request->cod_Utente];
            $updateUser->update(['codice' => $request->cod_Utente]);
        }

        if (isset($request->seu) && $updateUser->user_id_padre != $request->seu) {
            $changes['user_id_padre'] = ['old' => $updateUser->user_id_padre, 'new' => $request->seu];
            $updateUser->update(['user_id_padre' => $request->seu]);
        }

        if ($request->resetpwd == "true" || $request->resetpwd == 1) {
            $updateUser->update(['password' => '$2y$10$8fzMmLsdHiSm.70tmlkBN.f8e6LtDnnTLJ8t61MK/ak3MrA.eeo3W']);
            $changes['password'] = 'reset';
        }

        if (isset($request->activeUser)) {
            if ($updateUser->stato_user != $request->activeUser) {
                $changes['stato_user'] = ['old' => $updateUser->stato_user, 'new' => $request->activeUser];
            }
            $updateUser->update(['stato_user' => $request->activeUser]);
        }

        if (isset($request->contract_management)) {
            $exist = [];
            $requestIds = array_column($request->contract_management, 'id');

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

            // Remove associations no longer present
            $dbIds = contract_management::where('user_id', $request->idUtente)->pluck('macro_product_id')->toArray();
            $idsDaRimuovere = array_diff($dbIds, $requestIds);

            if (!empty($idsDaRimuovere)) {
                contract_management::where('user_id', $request->idUtente)
                                 ->whereIn('macro_product_id', $idsDaRimuovere)
                                 ->delete();
            }
        }

        // Log user update if there were changes
        if (!empty($changes)) {
            SystemLogService::userActivity()->info('User profile updated', [
                'updated_user_id' => $request->idUtente,
                'updated_user_email' => $updateUser->email,
                'updated_by_user_id' => Auth::id(),
                'changes' => $changes,
            ]);
        }

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => "Utente modificato ! " . $updateUser]]);
    }

    public function recuperaSEU(){
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 5) {
            $seu = User::where('role_id', 2)->orWhere('role_id', 5)->get();
        } else {
            $userId = Auth::user()->id;

            // Recursive function to extract all IDs from nested user structure
            function extractIds($users) {
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
            $ids = extractIds($seuSub);
            $ids[] = $userId;

            $seu = User::whereIn('id', $ids)->where('role_id', 2)->get();
        }

        return response()->json(["response" => "ok", "status" => "200", "body" => ["risposta" => $seu]]);
    }
}