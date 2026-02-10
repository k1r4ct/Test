<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class indirect extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'numero_livello',
        'percentuale_indiretta',
        'qualification_id',
    ];

    protected $casts = [
        'numero_livello' => 'integer',
        'percentuale_indiretta' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    public function qualification()
    {
        return $this->belongsTo(qualification::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log indirect percentage creation
        static::created(function ($indirect) {
            $indirect->load('qualification');
            
            $qualificationName = $indirect->qualification 
                ? $indirect->qualification->nome 
                : 'Qualification #' . $indirect->qualification_id;

            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Indirect percentage created", [
                'indirect_id' => $indirect->id,
                'numero_livello' => $indirect->numero_livello,
                'percentuale_indiretta' => $indirect->percentuale_indiretta,
                'qualification_id' => $indirect->qualification_id,
                'qualification_name' => $qualificationName,
                'created_by' => $userName,
            ]);
        });

        // Log indirect percentage updates
        static::updated(function ($indirect) {
            $changes = $indirect->getChanges();
            $original = $indirect->getOriginal();

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

                // Use warning for percentage changes as they affect calculations
                SystemLogService::database()->warning("Indirect percentage updated", [
                    'indirect_id' => $indirect->id,
                    'numero_livello' => $indirect->numero_livello,
                    'qualification_id' => $indirect->qualification_id,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log indirect percentage deletion
        static::deleted(function ($indirect) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Indirect percentage deleted", [
                'indirect_id' => $indirect->id,
                'numero_livello' => $indirect->numero_livello,
                'percentuale_indiretta' => $indirect->percentuale_indiretta,
                'qualification_id' => $indirect->qualification_id,
                'deleted_by' => $userName,
            ]);
        });
    }
}