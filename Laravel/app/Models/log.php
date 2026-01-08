<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

// ==================== LOG MODEL ====================

class Log extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'level',
        'source',
        'entity_type',
        'entity_id',
        'contract_id',
        'message',
        'datetime',
        'user_id',
        'context',
        'ip_address',
        'user_agent',
        'request_url',
        'request_method',
        'stack_trace',
        // Device tracking fields
        'device_fingerprint',
        'device_type',
        'device_os',
        'device_browser',
        'screen_resolution',
        'cpu_cores',
        'ram_gb',
        'timezone_client',
        'language',
        'touch_support',
        // Geolocation fields
        'geo_country',
        'geo_country_code',
        'geo_region',
        'geo_city',
        'geo_isp',
        'geo_timezone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cpu_cores' => 'integer',
        'ram_gb' => 'integer',
        'touch_support' => 'boolean',
    ];

    // ==================== LEVEL CONSTANTS ====================

    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';

    /**
     * Get all available log levels.
     *
     * @return array<string, string>
     */
    public static function getLevels(): array
    {
        return [
            self::LEVEL_DEBUG => 'Debug',
            self::LEVEL_INFO => 'Info',
            self::LEVEL_WARNING => 'Warning',
            self::LEVEL_ERROR => 'Error',
            self::LEVEL_CRITICAL => 'Critical',
        ];
    }

    // ==================== SOURCE CONSTANTS ====================

    public const SOURCE_AUTH = 'auth';
    public const SOURCE_API = 'api';
    public const SOURCE_DATABASE = 'database';
    public const SOURCE_SCHEDULER = 'scheduler';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_USER_ACTIVITY = 'user_activity';
    public const SOURCE_EXTERNAL_API = 'external_api';
    public const SOURCE_ECOMMERCE = 'ecommerce';

    /**
     * Get all available log sources.
     *
     * @return array<string, string>
     */
    public static function getSources(): array
    {
        return [
            self::SOURCE_AUTH => 'Autenticazione',
            self::SOURCE_API => 'API',
            self::SOURCE_DATABASE => 'Database',
            self::SOURCE_SCHEDULER => 'Scheduler',
            self::SOURCE_EMAIL => 'Email',
            self::SOURCE_SYSTEM => 'Sistema',
            self::SOURCE_USER_ACTIVITY => 'Attività Utente',
            self::SOURCE_EXTERNAL_API => 'API Esterne',
            self::SOURCE_ECOMMERCE => 'E-commerce',
        ];
    }

    /**
     * Get source labels for frontend (English keys).
     *
     * @return array<string, string>
     */
    public static function getSourceLabels(): array
    {
        return [
            self::SOURCE_AUTH => 'Auth',
            self::SOURCE_API => 'API',
            self::SOURCE_DATABASE => 'Database',
            self::SOURCE_SCHEDULER => 'Scheduler',
            self::SOURCE_EMAIL => 'Email',
            self::SOURCE_SYSTEM => 'System',
            self::SOURCE_USER_ACTIVITY => 'User Activity',
            self::SOURCE_EXTERNAL_API => 'External API',
            self::SOURCE_ECOMMERCE => 'E-commerce',
        ];
    }

    // ==================== ENTITY TYPE CONSTANTS ====================

    public const ENTITY_CONTRACT = 'contract';
    public const ENTITY_USER = 'user';
    public const ENTITY_CUSTOMER_DATA = 'customer_data';
    public const ENTITY_SPECIFIC_DATA = 'specific_data';
    public const ENTITY_LEAD = 'lead';
    public const ENTITY_ORDER = 'order';
    public const ENTITY_ARTICLE = 'article';
    public const ENTITY_PRODUCT = 'product';
    public const ENTITY_BACKOFFICE_NOTE = 'backoffice_note';

    /**
     * Get all available entity types.
     *
     * @return array<string, string>
     */
    public static function getEntityTypes(): array
    {
        return [
            self::ENTITY_CONTRACT => 'Contratto',
            self::ENTITY_USER => 'Utente',
            self::ENTITY_CUSTOMER_DATA => 'Dati Cliente',
            self::ENTITY_SPECIFIC_DATA => 'Dati Specifici',
            self::ENTITY_BACKOFFICE_NOTE => 'Nota Backoffice',
            self::ENTITY_LEAD => 'Lead',
            self::ENTITY_ORDER => 'Ordine',
            self::ENTITY_ARTICLE => 'Articolo',
            self::ENTITY_PRODUCT => 'Prodotto',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that generated this log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related contract (if any).
     */
    public function contract()
    {
        return $this->belongsTo(contract::class, 'contract_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Filter by log level.
     */
    public function scopeLevel(Builder $query, string|array $level): Builder
    {
        if (is_array($level)) {
            return $query->whereIn('level', $level);
        }
        return $query->where('level', $level);
    }

    /**
     * Scope: Filter by log source.
     */
    public function scopeSource(Builder $query, string|array $source): Builder
    {
        if (is_array($source)) {
            return $query->whereIn('source', $source);
        }
        return $query->where('source', $source);
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter logs from a specific date.
     */
    public function scopeFromDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('datetime', '>=', $date);
    }

    /**
     * Scope: Filter logs until a specific date.
     */
    public function scopeToDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('datetime', '<=', $date);
    }

    /**
     * Scope: Filter logs between dates.
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('datetime', '>=', $from)
                     ->whereDate('datetime', '<=', $to);
    }

    /**
     * Scope: Filter logs from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('datetime', today());
    }

    /**
     * Scope: Filter logs from last N days.
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->whereDate('datetime', '>=', now()->subDays($days));
    }

    /**
     * Scope: Search in message.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('message', 'LIKE', "%{$search}%");
    }

    /**
     * Scope: Only errors (error + critical).
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->whereIn('level', [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
    }

    /**
     * Scope: Only warnings and above.
     */
    public function scopeWarningsAndAbove(Builder $query): Builder
    {
        return $query->whereIn('level', [self::LEVEL_WARNING, self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
    }

    /**
     * Scope: System logs (no user).
     */
    public function scopeSystemOnly(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope: User logs only.
     */
    public function scopeUserOnly(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope: With stack trace (usually errors).
     */
    public function scopeWithStackTrace(Builder $query): Builder
    {
        return $query->whereNotNull('stack_trace');
    }

    /**
     * Scope: Order by most recent first.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('datetime', 'desc');
    }

    /**
     * Scope: E-commerce logs only.
     */
    public function scopeEcommerce(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_ECOMMERCE);
    }

    // ==================== AUDIT TRAIL SCOPES ====================

    /**
     * Scope: Filter by entity type.
     */
    public function scopeForEntityType(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope: Filter by specific entity (type + id).
     */
    public function scopeForEntity(Builder $query, string $entityType, int $entityId): Builder
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    /**
     * Scope: Filter by contract ID (direct or via entity).
     */
    public function scopeForContract(Builder $query, int $contractId): Builder
    {
        return $query->where(function ($q) use ($contractId) {
            $q->where('contract_id', $contractId)
              ->orWhere(function ($q2) use ($contractId) {
                  $q2->where('entity_type', self::ENTITY_CONTRACT)
                     ->where('entity_id', $contractId);
              });
        });
    }

    /**
     * Scope: Filter by contract code (searches in context JSON).
     */
    public function scopeForContractCode(Builder $query, string $contractCode): Builder
    {
        return $query->where(function ($q) use ($contractCode) {
            $q->whereJsonContains('context->contract_code', $contractCode)
              ->orWhere('message', 'LIKE', "%{$contractCode}%");
        });
    }

    /**
     * Scope: Only logs with entity tracking (audit logs).
     */
    public function scopeWithEntityTracking(Builder $query): Builder
    {
        return $query->whereNotNull('entity_type');
    }

    /**
     * Scope: Only contract-related logs.
     */
    public function scopeContractLogs(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('contract_id')
              ->orWhere('entity_type', self::ENTITY_CONTRACT);
        });
    }

    /**
     * Scope: Logs with changes (audit trail entries).
     */
    public function scopeWithChanges(Builder $query): Builder
    {
        return $query->whereNotNull('context')
                     ->whereRaw("JSON_EXTRACT(context, '$.changes') IS NOT NULL");
    }

    // ==================== DEVICE TRACKING SCOPES ====================

    /**
     * Scope: Filter by device fingerprint.
     */
    public function scopeForFingerprint(Builder $query, string $fingerprint): Builder
    {
        return $query->where('device_fingerprint', 'LIKE', "%{$fingerprint}%");
    }

    /**
     * Scope: Filter by country.
     */
    public function scopeForCountry(Builder $query, string $country): Builder
    {
        return $query->where(function ($q) use ($country) {
            $q->where('geo_country', 'LIKE', "%{$country}%")
              ->orWhere('geo_country_code', $country);
        });
    }

    /**
     * Scope: Filter by city.
     */
    public function scopeForCity(Builder $query, string $city): Builder
    {
        return $query->where('geo_city', 'LIKE', "%{$city}%");
    }

    /**
     * Scope: Filter by ISP.
     */
    public function scopeForIsp(Builder $query, string $isp): Builder
    {
        return $query->where('geo_isp', 'LIKE', "%{$isp}%");
    }

    /**
     * Scope: Filter by device type.
     */
    public function scopeForDeviceType(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope: Filter by OS.
     */
    public function scopeForOS(Builder $query, string $os): Builder
    {
        return $query->where('device_os', 'LIKE', "%{$os}%");
    }

    /**
     * Scope: Filter by browser.
     */
    public function scopeForBrowser(Builder $query, string $browser): Builder
    {
        return $query->where('device_browser', 'LIKE', "%{$browser}%");
    }

    /**
     * Scope: Filter by screen resolution.
     */
    public function scopeForScreenResolution(Builder $query, string $resolution): Builder
    {
        return $query->where('screen_resolution', $resolution);
    }

    /**
     * Scope: Filter by timezone.
     */
    public function scopeForTimezone(Builder $query, string $timezone): Builder
    {
        return $query->where(function ($q) use ($timezone) {
            $q->where('timezone_client', 'LIKE', "%{$timezone}%")
              ->orWhere('geo_timezone', 'LIKE', "%{$timezone}%");
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if this log has a stack trace.
     */
    public function hasStackTrace(): bool
    {
        return !empty($this->stack_trace);
    }

    /**
     * Check if this is an error level log.
     */
    public function isError(): bool
    {
        return in_array($this->level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
    }

    /**
     * Check if this is a warning level log.
     */
    public function isWarning(): bool
    {
        return $this->level === self::LEVEL_WARNING;
    }

    /**
     * Check if this log has entity tracking.
     */
    public function hasEntityTracking(): bool
    {
        return !empty($this->entity_type) && !empty($this->entity_id);
    }

    /**
     * Check if this log has tracked changes recorded in context.
     * 
     * IMPORTANT: Renamed from hasChanges() to avoid conflict with 
     * Eloquent's built-in Model::hasChanges($changes, $attributes) method.
     */
    public function hasTrackedChanges(): bool
    {
        return isset($this->context['changes']) && !empty($this->context['changes']);
    }

    /**
     * Get the level label.
     */
    public function getLevelLabelAttribute(): string
    {
        return self::getLevels()[$this->level] ?? $this->level;
    }

    /**
     * Get the source label.
     */
    public function getSourceLabelAttribute(): string
    {
        return self::getSources()[$this->source] ?? $this->source;
    }

    /**
     * Get the entity type label.
     */
    public function getEntityTypeLabelAttribute(): ?string
    {
        if (!$this->entity_type) {
            return null;
        }
        return self::getEntityTypes()[$this->entity_type] ?? $this->entity_type;
    }

    /**
     * Accessor for has_tracked_changes attribute (for JSON serialization).
     */
    public function getHasTrackedChangesAttribute(): bool
    {
        return $this->hasTrackedChanges();
    }

    /**
     * Get context value by key.
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Get changes from context.
     */
    public function getTrackedChanges(): array
    {
        return $this->context['changes'] ?? [];
    }

    /**
     * Get changed fields list.
     */
    public function getChangedFields(): array
    {
        return array_keys($this->getTrackedChanges());
    }

    /**
     * Get formatted datetime.
     */
    public function getFormattedDatetimeAttribute(): string
    {
        return $this->datetime?->format('d/m/Y H:i:s') ?? '';
    }

    /**
     * Get short message (truncated).
     */
    public function getShortMessageAttribute(): string
    {
        return \Str::limit($this->message, 100);
    }

    // ==================== STATIC HELPER METHODS ====================

    /**
     * Get log counts grouped by level.
     */
    public static function getCountsByLevel(?string $source = null): array
    {
        $query = self::query();
        
        if ($source) {
            $query->where('source', $source);
        }

        return $query->selectRaw('level, COUNT(*) as count')
                     ->groupBy('level')
                     ->pluck('count', 'level')
                     ->toArray();
    }

    /**
     * Get log counts grouped by source.
     */
    public static function getCountsBySource(): array
    {
        return self::selectRaw('source, COUNT(*) as count')
                   ->groupBy('source')
                   ->pluck('count', 'source')
                   ->toArray();
    }

    /**
     * Get log counts grouped by entity type.
     */
    public static function getCountsByEntityType(): array
    {
        return self::whereNotNull('entity_type')
                   ->selectRaw('entity_type, COUNT(*) as count')
                   ->groupBy('entity_type')
                   ->pluck('count', 'entity_type')
                   ->toArray();
    }

    /**
     * Get statistics for dashboard.
     */
    public static function getStats(?string $source = null): array
    {
        $query = self::query();
        
        if ($source) {
            $query->where('source', $source);
        }

        $total = $query->count();
        $byLevel = self::getCountsByLevel($source);
        $bySource = $source ? [] : self::getCountsBySource();
        $byEntityType = self::getCountsByEntityType();

        return [
            'total' => $total,
            'by_level' => [
                'debug' => $byLevel[self::LEVEL_DEBUG] ?? 0,
                'info' => $byLevel[self::LEVEL_INFO] ?? 0,
                'warning' => $byLevel[self::LEVEL_WARNING] ?? 0,
                'error' => $byLevel[self::LEVEL_ERROR] ?? 0,
                'critical' => $byLevel[self::LEVEL_CRITICAL] ?? 0,
            ],
            'by_source' => $bySource,
            'by_entity_type' => $byEntityType,
            'errors_today' => self::errors()->today()->count(),
            'audit_logs_today' => self::withEntityTracking()->today()->count(),
        ];
    }

    /**
     * Get contract history (all logs related to a contract).
     */
    public static function getContractHistory(int $contractId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return self::forContract($contractId)
                   ->with('user:id,name,cognome,email')
                   ->recent()
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get entity history.
     */
    public static function getEntityHistory(string $entityType, int $entityId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return self::forEntity($entityType, $entityId)
                   ->with('user:id,name,cognome,email')
                   ->recent()
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get available filters for frontend dropdown.
     */
    public static function getAvailableFilters(): array
    {
        return [
            'users' => User::select('id', 'name', 'cognome', 'email')
                          ->whereIn('id', self::distinct()->pluck('user_id')->filter())
                          ->get()
                          ->map(fn($u) => [
                              'id' => $u->id,
                              'name' => trim($u->name . ' ' . $u->cognome),
                              'email' => $u->email,
                          ]),
            'sources' => self::distinct()->pluck('source')->filter()->values(),
            'levels' => self::distinct()->pluck('level')->filter()->values(),
            'entity_types' => self::distinct()->pluck('entity_type')->filter()->values(),
        ];
    }

    /**
     * Clean old logs (older than N days).
     */
    public static function cleanOldLogs(int $days = 30): int
    {
        return self::where('datetime', '<', now()->subDays($days))->delete();
    }

    /**
     * Clean logs by source.
     */
    public static function cleanBySource(string $source): int
    {
        return self::where('source', $source)->delete();
    }
}