<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;

class customer_data extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'cognome',
        'email',
        'pec',
        'codice_fiscale',
        'telefono',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'nazione',
        'ragione_sociale',
        'partita_iva',
    ];

    // Fields that should be masked in logs for privacy
    protected static $sensitiveFields = [
        'codice_fiscale',
        'telefono',
        'partita_iva',
    ];

    // ==================== RELATIONSHIPS ====================

    public function contract()
    {
        return $this->hasMany(contract::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log customer creation
        static::created(function ($customer) {
            SystemLogService::database()->info("Customer data created", [
                'customer_data_id' => $customer->id,
                'nome' => $customer->nome,
                'cognome' => $customer->cognome,
                'email' => $customer->email,
                'ragione_sociale' => $customer->ragione_sociale,
                'citta' => $customer->citta,
                'provincia' => $customer->provincia,
                // Sensitive fields are masked
                'codice_fiscale' => static::maskSensitiveField($customer->codice_fiscale),
                'partita_iva' => static::maskSensitiveField($customer->partita_iva),
            ]);
        });

        // Log customer updates
        static::updated(function ($customer) {
            $changes = $customer->getChanges();
            $original = $customer->getOriginal();

            // Build changes array for logging
            $changesForLog = [];
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
                }
            }

            if (!empty($changesForLog)) {
                SystemLogService::database()->info("Customer data updated", [
                    'customer_data_id' => $customer->id,
                    'nome' => $customer->nome,
                    'cognome' => $customer->cognome,
                    'changes' => $changesForLog,
                ]);
            }
        });

        // Log customer deletion
        static::deleted(function ($customer) {
            SystemLogService::database()->warning("Customer data deleted", [
                'customer_data_id' => $customer->id,
                'nome' => $customer->nome,
                'cognome' => $customer->cognome,
                'email' => $customer->email,
                'ragione_sociale' => $customer->ragione_sociale,
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

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->nome . ' ' . $this->cognome);
    }

    /**
     * Get display name (ragione sociale or full name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->ragione_sociale ?: $this->full_name;
    }
}