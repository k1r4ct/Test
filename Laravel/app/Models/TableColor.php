<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;  

class TableColor extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'colore',
    ];

    // ==================== RELATIONSHIPS ====================

    public function LeadStatus()
    {
        return $this->hasMany(lead_status::class, 'color_id');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log color creation
        static::created(function ($color) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Table color created", [
                'table_color_id' => $color->id,
                'colore' => $color->colore,
                'created_by' => $userName,
            ]);
        });

        // Log color updates
        static::updated(function ($color) {
            $changes = $color->getChanges();
            $original = $color->getOriginal();

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

                SystemLogService::database()->info("Table color updated", [
                    'table_color_id' => $color->id,
                    'colore' => $color->colore,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log color deletion
        static::deleted(function ($color) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Table color deleted", [
                'table_color_id' => $color->id,
                'colore' => $color->colore,
                'affected_lead_statuses' => $color->LeadStatus()->count(),
                'deleted_by' => $userName,
            ]);
        });
    }
}