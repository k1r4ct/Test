<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;  

class status_contract extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'micro_stato',
    ];

    // ==================== RELATIONSHIPS ====================

    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    public function option_status_contract()
    {
        return $this->hasMany(option_status_contract::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log status creation
        static::created(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Contract status created", [
                'status_contract_id' => $status->id,
                'micro_stato' => $status->micro_stato,
                'created_by' => $userName,
            ]);
        });

        // Log status updates
        static::updated(function ($status) {
            $changes = $status->getChanges();
            $original = $status->getOriginal();

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

                SystemLogService::database()->warning("Contract status updated", [
                    'status_contract_id' => $status->id,
                    'micro_stato' => $status->micro_stato,
                    'changes' => $changesForLog,
                    'affected_contracts_count' => $status->contract()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log status deletion
        static::deleted(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Contract status deleted", [
                'status_contract_id' => $status->id,
                'micro_stato' => $status->micro_stato,
                'deleted_by' => $userName,
            ]);
        });
    }
}