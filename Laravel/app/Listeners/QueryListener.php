<?php

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use App\Services\SystemLogService;

class QueryListener
{
    /**
     * Slow query threshold in milliseconds.
     * Queries taking longer than this will be logged.
     * 
     * Can be configured via config or .env:
     * SLOW_QUERY_THRESHOLD=1000
     * 
     * @var int
     */
    protected int $slowQueryThreshold;

    /**
     * Whether query logging is enabled.
     * 
     * @var bool
     */
    protected bool $enabled;

    /**
     * Create a new listener instance.
     */
    public function __construct()
    {
        // Default: 1000ms (1 second)
        // Can be overridden in .env with SLOW_QUERY_THRESHOLD=500
        $this->slowQueryThreshold = (int) env('SLOW_QUERY_THRESHOLD', 1000);
        
        // Can be disabled in .env with LOG_SLOW_QUERIES=false
        $this->enabled = env('LOG_SLOW_QUERIES', true);
    }

    /**
     * Handle the QueryExecuted event.
     */
    public function handle(QueryExecuted $event): void
    {
        // Skip if disabled
        if (!$this->enabled) {
            return;
        }

        // Skip if query is fast enough
        if ($event->time < $this->slowQueryThreshold) {
            return;
        }

        // Format the query with bindings for better debugging
        $sql = $event->sql;
        $bindings = $event->bindings;
        $formattedSql = $this->formatQueryWithBindings($sql, $bindings);

        // Determine severity based on time
        $level = 'warning';
        if ($event->time > ($this->slowQueryThreshold * 3)) {
            $level = 'error'; // Very slow query (3x threshold)
        }

        // Build context
        $context = [
            'sql' => $sql,
            'time_ms' => round($event->time, 2),
            'time_seconds' => round($event->time / 1000, 3),
            'connection' => $event->connectionName,
            'threshold_ms' => $this->slowQueryThreshold,
        ];

        // Add bindings if not too many (avoid huge logs)
        if (count($bindings) <= 20) {
            $context['bindings'] = $bindings;
        } else {
            $context['bindings_count'] = count($bindings);
            $context['bindings_sample'] = array_slice($bindings, 0, 10);
        }

        // Log the slow query
        $message = sprintf(
            'Slow query detected: %.2fms (threshold: %dms)',
            $event->time,
            $this->slowQueryThreshold
        );

        if ($level === 'error') {
            SystemLogService::database()->error($message, $context);
        } else {
            SystemLogService::database()->warning($message, $context);
        }
    }

    /**
     * Format SQL query with bindings for readability.
     * 
     * @param string $sql
     * @param array $bindings
     * @return string
     */
    protected function formatQueryWithBindings(string $sql, array $bindings): string
    {
        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }

    /**
     * Get the current slow query threshold.
     * 
     * @return int
     */
    public function getThreshold(): int
    {
        return $this->slowQueryThreshold;
    }

    /**
     * Set the slow query threshold dynamically.
     * 
     * @param int $milliseconds
     * @return void
     */
    public function setThreshold(int $milliseconds): void
    {
        $this->slowQueryThreshold = $milliseconds;
    }

    /**
     * Enable query logging.
     * 
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable query logging.
     * 
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }
}
