<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class DetailQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_type_information_id',
        'opzione',
    ];

    // ==================== RELATIONSHIPS ====================

    public function CtypeInfo()
    {
        return $this->belongsTo(contract_type_information::class, 'contract_type_information_id');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log option creation
        static::created(function ($detailQuestion) {
            $detailQuestion->load('CtypeInfo');
            
            $questionText = $detailQuestion->CtypeInfo 
                ? $detailQuestion->CtypeInfo->domanda 
                : 'Question #' . $detailQuestion->contract_type_information_id;

            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Detail question option created", [
                'detail_question_id' => $detailQuestion->id,
                'contract_type_information_id' => $detailQuestion->contract_type_information_id,
                'question' => $questionText,
                'opzione' => $detailQuestion->opzione,
                'created_by' => $userName,
            ]);
        });

        // Log option updates
        static::updated(function ($detailQuestion) {
            $changes = $detailQuestion->getChanges();
            $original = $detailQuestion->getOriginal();

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

                SystemLogService::database()->info("Detail question option updated", [
                    'detail_question_id' => $detailQuestion->id,
                    'contract_type_information_id' => $detailQuestion->contract_type_information_id,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log option deletion
        static::deleted(function ($detailQuestion) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Detail question option deleted", [
                'detail_question_id' => $detailQuestion->id,
                'contract_type_information_id' => $detailQuestion->contract_type_information_id,
                'opzione' => $detailQuestion->opzione,
                'deleted_by' => $userName,
            ]);
        });
    }
}