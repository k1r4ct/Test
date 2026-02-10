<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class lead_status extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'applicabile_da',
        'micro_stato',
        'macro_stato',
        'fase',
        'specifica',
    ];

    // ==================== RELATIONSHIPS ====================

    public function Role()
    {
        return $this->belongsTo(Role::class);
    }

    public function lead()
    {
        return $this->hasMany(lead::class);
    }

    public function Colors()
    {
        return $this->belongsTo(TableColor::class, 'color_id');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log status creation
        static::created(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Lead status created", [
                'lead_status_id' => $status->id,
                'micro_stato' => $status->micro_stato,
                'macro_stato' => $status->macro_stato,
                'fase' => $status->fase,
                'specifica' => $status->specifica,
                'applicabile_da' => $status->applicabile_da,
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

                SystemLogService::database()->warning("Lead status updated", [
                    'lead_status_id' => $status->id,
                    'micro_stato' => $status->micro_stato,
                    'changes' => $changesForLog,
                    'affected_leads_count' => $status->lead()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log status deletion
        static::deleted(function ($status) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Lead status deleted", [
                'lead_status_id' => $status->id,
                'micro_stato' => $status->micro_stato,
                'macro_stato' => $status->macro_stato,
                'deleted_by' => $userName,
            ]);
        });
    }
}