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

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     /* protected $middleware = [
        'auth',
    ]; */


    public function index(Request $request)
    {
        /* $auth = Auth::guard('auth');
        
        if ($auth->check()) {
        } else {
            $ruoli=0;
        } */
        $ruoli=Role::all();
        $user=User::where('email',$request->email)->get();
        

        return $ruoli;
    }
public function index2(Request $request)
    {
        /* $auth = Auth::guard('auth');
        
        if ($auth->check()) {
        } else {
            $ruoli=0;
        } */
        $ruoli=Role::all();
        $user=User::where('email',$request->email)->get();
        

        return $ruoli;
    }
    public function prendiUtente(Request $request){
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
        $user=User::with('Role','qualification')->where('email',$request->email)->whereIn('id',$userIds)->get(['name','qualification_id','role_id','cognome','email']);

        return $user;
    }
    public function ruoliequalifiche()
    {
        $Auth = Auth::user()->role_id;
        if ($Auth != 1) {
            # code...
            $ruoli = Role::where('id', 3)->get(['descrizione', 'id']);
            $qualifiche = Qualification::where('id', 9)->get(['descrizione', 'id']);
        } else {
            $ruoli = Role::all(['descrizione', 'id']);
            $qualifiche = Qualification::all(['descrizione', 'id']);
        }

        return response()->json(["ruoli" => $ruoli, "qualifiche" => $qualifiche]);
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
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        //
    }
}
