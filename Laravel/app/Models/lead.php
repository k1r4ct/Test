<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class lead extends Model
{
    use HasFactory;

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
        return trim($this->nome . ' ' . $this->cognome);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log lead creation
        static::created(function ($lead) {
            $lead->load(['invitedBy', 'User']);

            $inviterName = $lead->invitedBy 
                ? $lead->invitedBy->name . ' ' . $lead->invitedBy->cognome 
                : null;

            $assignedToName = $lead->User 
                ? $lead->User->name . ' ' . $lead->User->cognome 
                : null;

            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->info("Lead created", [
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
                'consenso' => $lead->consenso,
                'created_by' => $userName,
            ]);
        });

        // Log lead updates (especially status changes and assignments)
        static::updated(function ($lead) {
            $changes = $lead->getChanges();
            $original = $lead->getOriginal();

            $changesForLog = [];
            $hasStatusChange = false;
            $hasAssignmentChange = false;

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

                    if ($field === 'lead_status_id') {
                        $hasStatusChange = true;
                    }
                    if ($field === 'assegnato_a') {
                        $hasAssignmentChange = true;
                    }
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                // Use warning for important changes
                $level = ($hasStatusChange || $hasAssignmentChange) ? 'warning' : 'info';

                SystemLogService::database()->{$level}("Lead updated", [
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->full_name,
                    'changes' => $changesForLog,
                    'status_changed' => $hasStatusChange,
                    'assignment_changed' => $hasAssignmentChange,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log lead deletion
        static::deleted(function ($lead) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::database()->warning("Lead deleted", [
                'lead_id' => $lead->id,
                'nome' => $lead->nome,
                'cognome' => $lead->cognome,
                'email' => $lead->email,
                'was_converted' => $lead->is_converted,
                'deleted_by' => $userName,
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