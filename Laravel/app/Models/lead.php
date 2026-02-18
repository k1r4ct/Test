<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemLogService;
use App\Traits\LogsDatabaseOperations;

class lead extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $appends = ['is_converted'];
    
    protected $fillable = [
        'invitato_da_user_id',
        'nome',
        'cognome',
        'telefono',
        'email',
        'lead_status_id',
        'assegnato_a',
        'consenso',
    ];

    // Sensitive fields to mask in logs
    protected static $sensitiveFields = ['telefono'];

    // Critical fields that warrant warning level logging
    protected static $criticalFields = ['lead_status_id', 'assegnato_a'];

    // ==================== RELATIONSHIPS ====================

    public function leadstatus()
    {
        return $this->belongsTo(lead_status::class, 'lead_status_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'assegnato_a');
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invitato_da_user_id');
    }

    public function leadConverted()
    {
        return $this->hasOne(leadConverted::class);
    }

    // ==================== ACCESSORS ====================

    public function getIsConvertedAttribute()
    {
        return !is_null($this->leadConverted);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->nome ?? '') . ' ' . ($this->cognome ?? ''));
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log lead creation with entity tracking
        static::created(function ($lead) {
            $lead->load(['invitedBy', 'User', 'leadstatus']);

            $inviterName = $lead->invitedBy 
                ? $lead->invitedBy->name . ' ' . $lead->invitedBy->cognome 
                : null;

            $assignedToName = $lead->User 
                ? $lead->User->name . ' ' . $lead->User->cognome 
                : null;

            $statusName = $lead->leadstatus?->micro_stato;

            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            // Use forEntity for audit trail
            SystemLogService::userActivity()
                ->forEntity('lead', $lead->id)
                ->info("Lead created", [
                    'lead_id' => $lead->id,
                    'nome' => $lead->nome,
                    'cognome' => $lead->cognome,
                    'email' => $lead->email,
                    'telefono' => static::maskSensitiveField($lead->telefono),
                    'invitato_da_user_id' => $lead->invitato_da_user_id,
                    'inviter_name' => $inviterName,
                    'assegnato_a' => $lead->assegnato_a,
                    'assigned_to_name' => $assignedToName,
                    'lead_status_id' => $lead->lead_status_id,
                    'status_name' => $statusName,
                    'consenso' => $lead->consenso,
                    'created_by' => $operatorName,
                ]);
        });

        // Log lead updates with change tracking
        static::updated(function ($lead) {
            $changes = $lead->getChanges();
            $original = $lead->getOriginal();

            $changesForLog = [];
            $hasCriticalChanges = false;

            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $oldValue = $original[$field] ?? null;

                    // Mask sensitive fields
                    if (in_array($field, static::$sensitiveFields)) {
                        $oldValue = static::maskSensitiveField($oldValue);
                        $newValue = static::maskSensitiveField($newValue);
                    }

                    $changesForLog[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];

                    if (in_array($field, static::$criticalFields)) {
                        $hasCriticalChanges = true;
                    }
                }
            }

            if (!empty($changesForLog)) {
                $operatorName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // Use warning for important changes (status, assignment)
                $level = $hasCriticalChanges ? 'warning' : 'info';

                // Enrich log with readable names
                $logData = [
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->full_name,
                    'changes' => $changesForLog,
                    'critical_change' => $hasCriticalChanges,
                    'updated_by' => $operatorName,
                ];

                // Add status names if status changed
                if (isset($changesForLog['lead_status_id'])) {
                    $oldStatus = lead_status::find($changesForLog['lead_status_id']['old']);
                    $newStatus = lead_status::find($changesForLog['lead_status_id']['new']);
                    $logData['old_status_name'] = $oldStatus?->micro_stato;
                    $logData['new_status_name'] = $newStatus?->micro_stato;
                }

                // Add user names if assignment changed
                if (isset($changesForLog['assegnato_a'])) {
                    $oldUser = User::find($changesForLog['assegnato_a']['old']);
                    $newUser = User::find($changesForLog['assegnato_a']['new']);
                    $logData['old_assigned_to_name'] = $oldUser 
                        ? $oldUser->name . ' ' . $oldUser->cognome 
                        : null;
                    $logData['new_assigned_to_name'] = $newUser 
                        ? $newUser->name . ' ' . $newUser->cognome 
                        : null;
                }

                // Use forEntity for audit trail
                SystemLogService::userActivity()
                    ->forEntity('lead', $lead->id)
                    ->{$level}("Lead updated", $logData);
            }
        });

        // Log lead deletion with entity tracking
        static::deleted(function ($lead) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            // Use forEntity for audit trail
            SystemLogService::userActivity()
                ->forEntity('lead', $lead->id)
                ->warning("Lead deleted", [
                    'lead_id' => $lead->id,
                    'nome' => $lead->nome,
                    'cognome' => $lead->cognome,
                    'email' => $lead->email,
                    'was_converted' => $lead->is_converted,
                    'deleted_by' => $operatorName,
                ]);
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Mask sensitive field for logging (show only last 4 chars)
     */
    protected static function maskSensitiveField($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($value, -4);
    }
}