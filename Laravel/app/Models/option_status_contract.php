<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class option_status_contract extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'macro_stato',
        'fase',
        'specifica',
        'genera_pv',
        'genera_pc',
        'status_contract_id',
        'applicabile_da_role_id',
    ];

    protected $casts = [
        'genera_pv' => 'boolean',
        'genera_pc' => 'boolean',
    ];

    // Critical fields that affect point generation
    protected static $criticalFields = ['genera_pv', 'genera_pc'];

    // ==================== RELATIONSHIPS ====================

    public function status_contract()
    {
        return $this->belongsTo(status_contract::class);
    }

    public function Role()
    {
        return $this->belongsTo(Role::class, 'applicabile_da_role_id');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log option status creation
        static::created(function ($optionStatus) {
            $optionStatus->load(['status_contract', 'Role']);

            $statusName = $optionStatus->status_contract 
                ? $optionStatus->status_contract->micro_stato 
                : 'Status #' . $optionStatus->status_contract_id;

            $roleName = $optionStatus->Role 
                ? $optionStatus->Role->descrizione 
                : null;

            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Option status contract created", [
                'option_status_id' => $optionStatus->id,
                'status_contract_id' => $optionStatus->status_contract_id,
                'status_name' => $statusName,
                'macro_stato' => $optionStatus->macro_stato,
                'fase' => $optionStatus->fase,
                'specifica' => $optionStatus->specifica,
                'genera_pv' => $optionStatus->genera_pv,
                'genera_pc' => $optionStatus->genera_pc,
                'applicabile_da_role_id' => $optionStatus->applicabile_da_role_id,
                'role_name' => $roleName,
                'created_by' => $userName,
            ]);
        });

        // Log option status updates (CRITICAL for genera_pv/genera_pc changes)
        static::updated(function ($optionStatus) {
            $changes = $optionStatus->getChanges();
            $original = $optionStatus->getOriginal();

            $changesForLog = [];
            $hasCriticalChanges = false;

            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];

                    if (in_array($field, static::$criticalFields)) {
                        $hasCriticalChanges = true;
                    }
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // CRITICAL level for genera_pv/genera_pc changes!
                $level = $hasCriticalChanges ? 'critical' : 'warning';

                SystemLogService::database()->{$level}("Option status contract updated", [
                    'option_status_id' => $optionStatus->id,
                    'status_contract_id' => $optionStatus->status_contract_id,
                    'macro_stato' => $optionStatus->macro_stato,
                    'changes' => $changesForLog,
                    'critical_change' => $hasCriticalChanges,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log option status deletion
        static::deleted(function ($optionStatus) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Option status contract deleted", [
                'option_status_id' => $optionStatus->id,
                'status_contract_id' => $optionStatus->status_contract_id,
                'macro_stato' => $optionStatus->macro_stato,
                'genera_pv' => $optionStatus->genera_pv,
                'genera_pc' => $optionStatus->genera_pc,
                'deleted_by' => $userName,
            ]);
        });
    }
}