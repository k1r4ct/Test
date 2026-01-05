<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domanda',
        'tipo_risposta',
    ];

    // ==================== RELATIONSHIPS ====================

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log survey creation
        static::created(function ($survey) {
            $survey->load('User');

            $userName = $survey->User 
                ? $survey->User->name . ' ' . $survey->User->cognome 
                : 'User #' . $survey->user_id;

            $creatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Survey created", [
                'survey_id' => $survey->id,
                'user_id' => $survey->user_id,
                'user_name' => $userName,
                'domanda' => \Str::limit($survey->domanda, 100),
                'tipo_risposta' => $survey->tipo_risposta,
                'created_by' => $creatorName,
            ]);
        });

        // Log survey updates
        static::updated(function ($survey) {
            $changes = $survey->getChanges();
            $original = $survey->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $field === 'domanda' ? \Str::limit($original[$field] ?? '', 100) : ($original[$field] ?? null),
                        'new' => $field === 'domanda' ? \Str::limit($newValue, 100) : $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                $operatorName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                SystemLogService::database()->info("Survey updated", [
                    'survey_id' => $survey->id,
                    'user_id' => $survey->user_id,
                    'changes' => $changesForLog,
                    'updated_by' => $operatorName,
                ]);
            }
        });

        // Log survey deletion
        static::deleted(function ($survey) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Survey deleted", [
                'survey_id' => $survey->id,
                'user_id' => $survey->user_id,
                'domanda' => \Str::limit($survey->domanda, 100),
                'deleted_by' => $operatorName,
            ]);
        });
    }
}