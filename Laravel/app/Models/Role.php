<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'descrizione',
    ];

    // ==================== RELATIONSHIPS ====================

    public function User()
    {
        return $this->hasMany(User::class);
    }

    public function leadstatus()
    {
        return $this->hasMany(lead_status::class);
    }

    public function OptionStatus()
    {
        return $this->hasMany(Option_status_contract::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log role creation
        static::created(function ($role) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Role created", [
                'role_id' => $role->id,
                'descrizione' => $role->descrizione,
                'created_by' => $userName,
            ]);
        });

        // Log role updates
        static::updated(function ($role) {
            $changes = $role->getChanges();
            $original = $role->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                SystemLogService::database()->warning("Role updated", [
                    'role_id' => $role->id,
                    'descrizione' => $role->descrizione,
                    'changes' => $changesForLog,
                    'affected_users_count' => $role->User()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log role deletion
        static::deleted(function ($role) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Role deleted", [
                'role_id' => $role->id,
                'descrizione' => $role->descrizione,
                'deleted_by' => $userName,
            ]);
        });
    }
}