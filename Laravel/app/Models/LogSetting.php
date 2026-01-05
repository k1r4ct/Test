<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LogSetting extends Model
{
    use HasFactory;

    protected $table = 'log_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Cache key for settings
     */
    const CACHE_KEY = 'log_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::getAllCached();
        
        if (!isset($settings[$key])) {
            return $default;
        }

        return self::castValue($settings[$key]['value'], $settings[$key]['type']);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): bool
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return false;
        }

        // Convert boolean to string
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $setting->value = (string) $value;
        $setting->save();

        // Clear cache
        self::clearCache();

        return true;
    }

    /**
     * Get all settings cached
     */
    public static function getAllCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::all()->keyBy('key')->toArray();
        });
    }

    /**
     * Get settings grouped by group
     */
    public static function getGrouped(): array
    {
        $settings = self::all();
        $grouped = [];

        foreach ($settings as $setting) {
            $grouped[$setting->group][] = [
                'key' => $setting->key,
                'value' => self::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'label' => $setting->label,
                'description' => $setting->description,
            ];
        }

        return $grouped;
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Cast value to proper type
     */
    private static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Get retention days for a specific source
     */
    public static function getRetentionDays(string $source): int
    {
        $key = 'retention_' . $source;
        return self::get($key, 30); // Default 30 days
    }

    /**
     * Check if logging to database is enabled
     */
    public static function isLogToDatabaseEnabled(): bool
    {
        return self::get('log_to_database', true);
    }

    /**
     * Check if logging to file is enabled
     */
    public static function isLogToFileEnabled(): bool
    {
        return self::get('log_to_file', true);
    }

    /**
     * Check if cleanup is enabled
     */
    public static function isCleanupEnabled(): bool
    {
        return self::get('cleanup_enabled', true);
    }

    /**
     * Get cleanup frequency
     */
    public static function getCleanupFrequency(): string
    {
        return self::get('cleanup_frequency', 'daily');
    }

    /**
     * Get cleanup time
     */
    public static function getCleanupTime(): string
    {
        return self::get('cleanup_time', '03:00');
    }

    /**
     * Update last cleanup run time
     */
    public static function updateLastCleanupRun(): void
    {
        self::set('cleanup_last_run', now()->format('Y-m-d H:i:s'));
    }
}
