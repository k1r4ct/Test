<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class qualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'descrizione',
        'pc_necessari',
        'compenso_pvdiretti',
        'pc_bonus_mensile',
    ];

    protected $casts = [
        'pc_necessari' => 'integer',
        'compenso_pvdiretti' => 'decimal:2',
        'pc_bonus_mensile' => 'integer',
    ];

    // Critical fields that affect career progression
    protected static $criticalFields = ['pc_necessari', 'compenso_pvdiretti', 'pc_bonus_mensile'];

    // ==================== RELATIONSHIPS ====================

    public function User()
    {
        return $this->hasMany(User::class);
    }

    public function indirect()
    {
        return $this->hasMany(indirect::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log qualification creation
        static::created(function ($qualification) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Qualification created", [
                'qualification_id' => $qualification->id,
                'descrizione' => $qualification->descrizione,
                'pc_necessari' => $qualification->pc_necessari,
                'compenso_pvdiretti' => $qualification->compenso_pvdiretti,
                'pc_bonus_mensile' => $qualification->pc_bonus_mensile,
                'created_by' => $userName,
            ]);
        });

        // Log qualification updates (CRITICAL for career progression)
        static::updated(function ($qualification) {
            $changes = $qualification->getChanges();
            $original = $qualification->getOriginal();

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

                // Use warning for changes affecting career progression
                $level = $hasCriticalChanges ? 'warning' : 'info';

                SystemLogService::database()->{$level}("Qualification updated", [
                    'qualification_id' => $qualification->id,
                    'descrizione' => $qualification->descrizione,
                    'changes' => $changesForLog,
                    'critical_change' => $hasCriticalChanges,
                    'affected_users_count' => $qualification->User()->count(),
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log qualification deletion
        static::deleted(function ($qualification) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Qualification deleted", [
                'qualification_id' => $qualification->id,
                'descrizione' => $qualification->descrizione,
                'pc_necessari' => $qualification->pc_necessari,
                'deleted_by' => $userName,
            ]);
        });
    }
}