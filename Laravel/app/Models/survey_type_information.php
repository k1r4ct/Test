<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class survey_type_information extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $fillable = [
        'domanda',
        'risposta_tipo_numero',
        'risposta_tipo_stringa',
    ];

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log creation
        static::created(function ($surveyInfo) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Survey type information created", [
                'survey_type_info_id' => $surveyInfo->id,
                'domanda' => \Str::limit($surveyInfo->domanda, 100),
                'risposta_tipo_numero' => $surveyInfo->risposta_tipo_numero,
                'risposta_tipo_stringa' => $surveyInfo->risposta_tipo_stringa,
                'created_by' => $userName,
            ]);
        });

        // Log updates
        static::updated(function ($surveyInfo) {
            $changes = $surveyInfo->getChanges();
            $original = $surveyInfo->getOriginal();

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

                SystemLogService::database()->info("Survey type information updated", [
                    'survey_type_info_id' => $surveyInfo->id,
                    'domanda' => \Str::limit($surveyInfo->domanda, 100),
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log deletion
        static::deleted(function ($surveyInfo) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Survey type information deleted", [
                'survey_type_info_id' => $surveyInfo->id,
                'domanda' => \Str::limit($surveyInfo->domanda, 100),
                'deleted_by' => $userName,
            ]);
        });
    }
}