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
        'message',
        'datetime',
        'user_id',
        'context',
        'ip_address',
        'user_agent',
        'request_url',
        'request_method',
        'stack_trace',
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

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that generated this log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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
     * Get context value by key.
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
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
            'errors_today' => self::errors()->today()->count(),
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