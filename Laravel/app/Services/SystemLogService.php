<?php

namespace App\Services;

use App\Models\Log;
use App\Models\LogSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Facades\Mail;
use App\Mail\CriticalErrorNotification;

class SystemLogService
{
    /**
     * Current log source context.
     *
     * @var string|null
     */
    protected ?string $source = null;

    /**
     * Entity tracking properties for audit trail.
     */
    protected ?string $entityType = null;
    protected ?int $entityId = null;
    protected ?int $contractId = null;

    /**
     * Create a new service instance with a specific source.
     */
    public function __construct(?string $source = null)
    {
        $this->source = $source;
    }

    // ==================== STATIC SOURCE FACTORIES ====================

    /**
     * Create logger for Auth source.
     */
    public static function auth(): self
    {
        return new self(Log::SOURCE_AUTH);
    }

    /**
     * Create logger for API source.
     */
    // public static function api(): self
    // {
    //     return new self(Log::SOURCE_API);
    // }

    /**
     * Create logger for Database source.
     */
    public static function database(): self
    {
        return new self(Log::SOURCE_DATABASE);
    }

    /**
     * Create logger for Scheduler source.
     */
    public static function scheduler(): self
    {
        return new self(Log::SOURCE_SCHEDULER);
    }

    /**
     * Create logger for Email source.
     */
    public static function email(): self
    {
        return new self(Log::SOURCE_EMAIL);
    }

    /**
     * Create logger for System source.
     */
    public static function system(): self
    {
        return new self(Log::SOURCE_SYSTEM);
    }

    /**
     * Create logger for User Activity source.
     */
    public static function userActivity(): self
    {
        return new self(Log::SOURCE_USER_ACTIVITY);
    }

    /**
     * Create logger for External API source (Google Sheets, external integrations).
     */
    public static function externalApi(): self
    {
        return new self(Log::SOURCE_EXTERNAL_API);
    }

    /**
     * Create logger for E-commerce source (orders, cart, articles, stock).
     */
    public static function ecommerce(): self
    {
        return new self(Log::SOURCE_ECOMMERCE);
    }

    // ==================== FLUENT ENTITY SETTERS ====================

    /**
     * Set the entity being logged (for audit trail).
     * 
     * Usage: SystemLogService::userActivity()->forEntity('contract', $id)->info(...)
     *
     * @param string $type Entity type (contract, user, customer_data, specific_data, etc.)
     * @param int $id Entity ID
     * @return self
     */
    public function forEntity(string $type, int $id): self
    {
        $this->entityType = $type;
        $this->entityId = $id;
        return $this;
    }

    /**
     * Set the related contract (for quick filtering).
     * 
     * Usage: SystemLogService::userActivity()->forContract($contractId)->info(...)
     *
     * @param int $contractId Contract ID
     * @return self
     */
    public function forContract(int $contractId): self
    {
        $this->contractId = $contractId;
        return $this;
    }

    /**
     * Shortcut to set both entity and contract for contract logs.
     * 
     * Usage: SystemLogService::userActivity()->onContract($contract)->info(...)
     *
     * @param \App\Models\contract $contract Contract model instance
     * @return self
     */
    public function onContract($contract): self
    {
        $this->entityType = 'contract';
        $this->entityId = $contract->id;
        $this->contractId = $contract->id;
        return $this;
    }

    /**
     * Reset entity tracking properties after logging.
     */
    protected function resetEntityTracking(): void
    {
        $this->entityType = null;
        $this->entityId = null;
        $this->contractId = null;
    }

    // ==================== LOG LEVEL METHODS ====================

    /**
     * Log a debug message.
     */
    public function debug(string $message, array $context = []): ?Log
    {
        return $this->createLog(Log::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     */
    public function info(string $message, array $context = []): ?Log
    {
        return $this->createLog(Log::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a warning message.
     */
    public function warning(string $message, array $context = []): ?Log
    {
        return $this->createLog(Log::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an error message.
     */
    public function error(string $message, array $context = [], ?\Throwable $exception = null): ?Log
    {
        if ($exception) {
            $context['exception_class'] = get_class($exception);
            $context['exception_code'] = $exception->getCode();
        }

        $stackTrace = $exception ? $this->formatStackTrace($exception) : null;

        $log = $this->createLog(Log::LEVEL_ERROR, $message, $context, $stackTrace);

        // Send notification for errors too (if enabled)
        $this->notifyIfCritical('error', $message, $context);

        return $log;
    }

    /**
     * Log a critical message.
     */
    public function critical(string $message, array $context = [], ?\Throwable $exception = null): ?Log
    {
        if ($exception) {
            $context['exception_class'] = get_class($exception);
            $context['exception_code'] = $exception->getCode();
        }

        $stackTrace = $exception ? $this->formatStackTrace($exception) : null;

        $log = $this->createLog(Log::LEVEL_CRITICAL, $message, $context, $stackTrace);

        // Send notification for critical errors
        $this->notifyIfCritical('critical', $message, $context);

        return $log;
    }

    // ==================== GENERIC LOG METHOD ====================

    /**
     * Create a log entry with specified level and source.
     * 
     * @param string $level Log level (debug, info, warning, error, critical)
     * @param string $source Log source (auth, database, scheduler, email, system, user_activity)
     * @param string $message Log message
     * @param array $context Additional context data
     * @param string|null $stackTrace Stack trace for errors
     */
    public static function log(
        string $level,
        string $source,
        string $message,
        array $context = [],
        ?string $stackTrace = null
    ): ?Log {
        $instance = new self($source);
        return $instance->createLog($level, $message, $context, $stackTrace);
    }

    // ==================== CORE LOG CREATION ====================

    /**
     * Create the log entry in the database and optionally in log files.
     */
    protected function createLog(
        string $level,
        string $message,
        array $context = [],
        ?string $stackTrace = null
    ): ?Log {
        // Get current user if authenticated
        $userId = null;
        try {
            $userId = Auth::id();
        } catch (\Exception $e) {
            // Auth not available (e.g., in console commands)
        }

        // Get request information
        $ipAddress = null;
        $userAgent = null;
        $requestUrl = null;
        $requestMethod = null;

        try {
            if (app()->runningInConsole() === false) {
                $ipAddress = Request::ip();
                $userAgent = Request::userAgent();
                $requestUrl = Request::fullUrl();
                $requestMethod = Request::method();
            }
        } catch (\Exception $e) {
            // Request not available
        }

        $source = $this->source ?? Log::SOURCE_SYSTEM;
        $log = null;

        // Extract entity tracking from context if set there (alternative method)
        $entityType = $this->entityType ?? ($context['_entity_type'] ?? null);
        $entityId = $this->entityId ?? ($context['_entity_id'] ?? null);
        $contractId = $this->contractId ?? ($context['_contract_id'] ?? null);

        // Also check for contract_id in context (common pattern)
        if (!$contractId && isset($context['contract_id'])) {
            $contractId = $context['contract_id'];
        }

        // Remove internal keys from context before saving
        unset($context['_entity_type'], $context['_entity_id'], $context['_contract_id']);

        // Write to database if enabled
        if ($this->isLogToDatabaseEnabled()) {
            try {
                // Get device info from middleware
                $deviceInfo = null;
                try {
                    $deviceInfo = app('device_info');
                } catch (\Exception $e) {
                    // Device info not available
                }

                $log = Log::create([
                    'level' => $level,
                    'source' => $source,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'contract_id' => $contractId,
                    'message' => $message,
                    'datetime' => now(),
                    'user_id' => $userId,
                    'context' => !empty($context) ? $context : null,
                    'ip_address' => $deviceInfo['ip_address'] ?? $ipAddress,
                    'user_agent' => $deviceInfo['user_agent'] ?? $userAgent,
                    'request_url' => $requestUrl,
                    'request_method' => $requestMethod,
                    'stack_trace' => $stackTrace,
                    // Device tracking fields
                    'device_fingerprint' => $deviceInfo['device_fingerprint'] ?? null,
                    'device_type' => $deviceInfo['device_type'] ?? null,
                    'device_os' => $deviceInfo['device_os'] ?? null,
                    'device_browser' => $deviceInfo['device_browser'] ?? null,
                    'screen_resolution' => $deviceInfo['screen_resolution'] ?? null,
                    'cpu_cores' => $deviceInfo['cpu_cores'] ?? null,
                    'ram_gb' => $deviceInfo['ram_gb'] ?? null,
                    'timezone_client' => $deviceInfo['timezone_client'] ?? null,
                    'language' => $deviceInfo['language'] ?? null,
                    'touch_support' => $deviceInfo['touch_support'] ?? null,
                    // Geolocation fields
                    'geo_country' => $deviceInfo['geo_country'] ?? null,
                    'geo_country_code' => $deviceInfo['geo_country_code'] ?? null,
                    'geo_region' => $deviceInfo['geo_region'] ?? null,
                    'geo_city' => $deviceInfo['geo_city'] ?? null,
                    'geo_isp' => $deviceInfo['geo_isp'] ?? null,
                    'geo_timezone' => $deviceInfo['geo_timezone'] ?? null,
                ]);
            } catch (\Exception $e) {
                // If database logging fails, at least try to log to file
                LaravelLog::error('Failed to write log to database', [
                    'error' => $e->getMessage(),
                    'original_message' => $message,
                ]);
            }
        }

        // Write to log file if enabled
        if ($this->isLogToFileEnabled()) {
            $this->writeToLogFile($level, $source, $message, $context, $stackTrace);
        }

        // Reset entity tracking for next use
        $this->resetEntityTracking();

        return $log;
    }

    /**
     * Write log to the appropriate log file channel.
     */
    protected function writeToLogFile(
        string $level,
        string $source,
        string $message,
        array $context = [],
        ?string $stackTrace = null
    ): void {
        try {
            // Map our source to Laravel log channel
            $channel = $this->getChannelForSource($source);
            
            // Build the full message
            $fullMessage = "[{$source}] {$message}";
            
            // Add stack trace to context if present
            if ($stackTrace) {
                $context['stack_trace'] = $stackTrace;
            }

            // Add user info to context
            if (Auth::check()) {
                $context['user_id'] = Auth::id();
                $context['user_email'] = Auth::user()->email ?? null;
            }

            // Write to the specific channel
            LaravelLog::channel($channel)->{$level}($fullMessage, $context);

            // Also write errors to the errors channel
            if (in_array($level, [Log::LEVEL_ERROR, Log::LEVEL_CRITICAL])) {
                LaravelLog::channel('errors')->{$level}($fullMessage, $context);
            }

        } catch (\Exception $e) {
            // Silently fail - don't break the app if file logging fails
            // But still write to default Laravel log
            LaravelLog::error('Failed to write to custom log channel: ' . $e->getMessage());
        }
    }

    /**
     * Get the Laravel log channel name for a given source.
     */
    protected function getChannelForSource(string $source): string
    {
        $channelMap = [
            Log::SOURCE_AUTH => 'auth',
            // Log::SOURCE_API => 'api',
            Log::SOURCE_DATABASE => 'database',
            Log::SOURCE_SCHEDULER => 'scheduler',
            Log::SOURCE_EMAIL => 'email',
            Log::SOURCE_SYSTEM => 'system',
            Log::SOURCE_USER_ACTIVITY => 'user_activity',
            Log::SOURCE_EXTERNAL_API => 'external_api',
        ];

        return $channelMap[$source] ?? 'single';
    }

    // ==================== SETTINGS HELPERS ====================

    /**
     * Check if logging to database is enabled (reads from log_settings table).
     */
    protected function isLogToDatabaseEnabled(): bool
    {
        try {
            return LogSetting::get('log_to_database', true);
        } catch (\Exception $e) {
            // If settings table doesn't exist yet, default to true
            return true;
        }
    }

    /**
     * Check if logging to file is enabled (reads from log_settings table).
     */
    protected function isLogToFileEnabled(): bool
    {
        try {
            return LogSetting::get('log_to_file', true);
        } catch (\Exception $e) {
            // If settings table doesn't exist yet, default to true
            return true;
        }
    }

    // ==================== EMAIL NOTIFICATION ====================

    /**
     * Send notification for critical/error level logs if enabled in settings.
     * 
     * @param string $level The log level (error, critical)
     * @param string $message The error message
     * @param array $context Additional context data
     */
    protected function notifyIfCritical(string $level, string $message, array $context): void
    {
        try {
            // Check if notifications are enabled
            $notifyEnabled = LogSetting::get('notify_critical_errors', false);
            
            if (!$notifyEnabled) {
                return;
            }

            // Get notification email
            $email = LogSetting::get('notify_email');
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            // Remove internal/sensitive data from context before sending
            $safeContext = $this->sanitizeContextForEmail($context);

            // Add source info to context
            $safeContext['source'] = $this->source ?? 'system';

            // Send email notification (queued for better performance)
            // Use send() instead of queue() if you don't have queue workers running
            Mail::to($email)->send(new CriticalErrorNotification($message, $safeContext, $level));
            
        } catch (\Exception $e) {
            // Silently fail - don't break logging because notification failed
            // Optionally log to file only to avoid recursion
            LaravelLog::channel('single')->warning('Failed to send error notification email: ' . $e->getMessage());
        }
    }

    /**
     * Remove sensitive data from context before sending via email.
     * 
     * @param array $context Original context
     * @return array Sanitized context
     */
    protected function sanitizeContextForEmail(array $context): array
    {
        // Keys to exclude from email notifications
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'api_key', 'secret',
            'authorization', 'cookie', 'session', '_token', 'csrf',
            '_entity_type', '_entity_id', '_contract_id', // Internal tracking keys
        ];

        $safeContext = [];
        
        foreach ($context as $key => $value) {
            // Skip internal keys (starting with _)
            if (str_starts_with($key, '_')) {
                continue;
            }
            
            // Skip sensitive keys (case insensitive)
            $keyLower = strtolower($key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($keyLower, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }
            if ($isSensitive) {
                continue;
            }
            
            // Truncate very long values
            if (is_string($value) && strlen($value) > 500) {
                $value = substr($value, 0, 500) . '... [truncated]';
            }
            
            // Convert arrays/objects to limited string representation
            if (is_array($value) || is_object($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
                if (strlen($encoded) > 500) {
                    $value = substr($encoded, 0, 500) . '... [truncated]';
                } else {
                    $value = $encoded;
                }
            }
            
            $safeContext[$key] = $value;
        }
        
        return $safeContext;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Format exception stack trace.
     */
    protected function formatStackTrace(\Throwable $exception): string
    {
        $trace = $exception->getMessage() . "\n";
        $trace .= "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n\n";
        $trace .= "Stack Trace:\n";
        $trace .= $exception->getTraceAsString();

        return $trace;
    }

    /**
     * Log with automatic exception handling.
     * Useful in catch blocks.
     */
    public function exception(\Throwable $exception, string $message = '', array $context = []): ?Log
    {
        $logMessage = $message ?: $exception->getMessage();
        
        return $this->error($logMessage, $context, $exception);
    }

    // ==================== CONVENIENCE STATIC METHODS ====================

    /**
     * Quick log for user login.
     */
    public static function logLogin(int $userId, string $email, bool $success = true): ?Log
    {
        $service = self::auth()->forEntity('user', $userId);
        
        if ($success) {
            return $service->info("User logged in successfully", [
                'user_id' => $userId,
                'email' => $email,
            ]);
        }

        return $service->warning("Failed login attempt", [
            'email' => $email,
        ]);
    }

    /**
     * Quick log for user logout.
     */
    public static function logLogout(int $userId, string $email): ?Log
    {
        return self::auth()->forEntity('user', $userId)->info("User logged out", [
            'user_id' => $userId,
            'email' => $email,
        ]);
    }

    /**
     * Quick log for password change.
     */
    public static function logPasswordChange(int $userId, string $email): ?Log
    {
        return self::auth()->forEntity('user', $userId)->info("Password changed", [
            'user_id' => $userId,
            'email' => $email,
        ]);
    }

    /**
     * Quick log for API request.
     */
    // public static function logApiRequest(string $endpoint, string $method, int $statusCode, ?float $duration = null): ?Log
    // {
    //     $level = $statusCode >= 500 ? Log::LEVEL_ERROR : 
    //             ($statusCode >= 400 ? Log::LEVEL_WARNING : Log::LEVEL_INFO);
    //
    //     $context = [
    //         'endpoint' => $endpoint,
    //         'method' => $method,
    //         'status_code' => $statusCode,
    //     ];
    //
    //     if ($duration !== null) {
    //         $context['duration_ms'] = round($duration * 1000, 2);
    //     }
    //
    //     return self::log($level, Log::SOURCE_API, "API {$method} {$endpoint} - {$statusCode}", $context);
    // }

    /**
     * Quick log for slow database query.
     */
    public static function logSlowQuery(string $query, float $timeInSeconds, array $bindings = []): ?Log
    {
        return self::database()->warning("Slow query detected", [
            'query' => $query,
            'time_seconds' => round($timeInSeconds, 4),
            'bindings' => $bindings,
        ]);
    }

    /**
     * Quick log for email sent.
     */
    public static function logEmailSent(string $to, string $subject, bool $success = true): ?Log
    {
        $service = self::email();

        if ($success) {
            return $service->info("Email sent successfully", [
                'to' => $to,
                'subject' => $subject,
            ]);
        }

        return $service->error("Failed to send email", [
            'to' => $to,
            'subject' => $subject,
        ]);
    }

    /**
     * Quick log for scheduled job.
     */
    public static function logScheduledJob(string $jobName, bool $success = true, ?string $details = null): ?Log
    {
        $service = self::scheduler();
        $context = ['job_name' => $jobName];

        if ($details) {
            $context['details'] = $details;
        }

        if ($success) {
            return $service->info("Scheduled job completed: {$jobName}", $context);
        }

        return $service->error("Scheduled job failed: {$jobName}", $context);
    }

    /**
     * Quick log for user activity (generic).
     */
    public static function logActivity(string $action, array $context = []): ?Log
    {
        return self::userActivity()->info($action, $context);
    }

    /**
     * Quick log for contract status change.
     */
    public static function logContractStatusChange(
        int $contractId,
        string $oldStatus,
        string $newStatus,
        ?int $userId = null
    ): ?Log {
        $userName = 'Sistema';
        
        if ($userId) {
            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : "User #{$userId}";
        } elseif (Auth::check()) {
            $userName = Auth::user()->name;
        }

        $message = "L'utente {$userName} ha modificato lo stato di avanzamento del contratto con id {$contractId} da {$oldStatus} a {$newStatus}";

        return self::userActivity()
            ->forEntity('contract', $contractId)
            ->forContract($contractId)
            ->info($message, [
                'contract_id' => $contractId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_name' => $userName,
                'changes' => [
                    'status' => ['old' => $oldStatus, 'new' => $newStatus]
                ],
            ]);
    }

    /**
     * Quick log for contract modification with changes tracking.
     * 
     * @param int $contractId Contract ID
     * @param string|null $contractCode Contract code (codice_contratto)
     * @param array $changes Array of changes ['field' => ['old' => x, 'new' => y]]
     * @param string|null $action Action description (created, updated, deleted)
     */
    public static function logContractChange(
        int $contractId,
        ?string $contractCode,
        array $changes,
        string $action = 'updated'
    ): ?Log {
        $changedFields = array_keys($changes);
        $message = "Contract {$contractCode} {$action}";
        
        if ($action === 'updated' && count($changedFields) > 0) {
            $message .= " (" . count($changedFields) . " fields changed)";
        }

        return self::userActivity()
            ->forEntity('contract', $contractId)
            ->forContract($contractId)
            ->info($message, [
                'contract_id' => $contractId,
                'contract_code' => $contractCode,
                'action' => $action,
                'changes' => $changes,
                'changed_fields' => $changedFields,
            ]);
    }

    /**
     * Quick log for customer data modification.
     * 
     * @param int $customerDataId Customer data ID
     * @param int|null $contractId Related contract ID
     * @param array $changes Array of changes
     * @param string $action Action description
     */
    public static function logCustomerDataChange(
        int $customerDataId,
        ?int $contractId,
        array $changes,
        string $action = 'updated'
    ): ?Log {
        $service = self::userActivity()->forEntity('customer_data', $customerDataId);
        
        if ($contractId) {
            $service->forContract($contractId);
        }

        return $service->info("Customer data {$action}", [
            'customer_data_id' => $customerDataId,
            'contract_id' => $contractId,
            'action' => $action,
            'changes' => $changes,
            'changed_fields' => array_keys($changes),
        ]);
    }

    /**
     * Quick log for specific data modification.
     * 
     * @param int $specificDataId Specific data ID
     * @param int|null $contractId Related contract ID
     * @param array $changes Array of changes
     * @param string $action Action description
     */
    public static function logSpecificDataChange(
        int $specificDataId,
        ?int $contractId,
        array $changes,
        string $action = 'updated'
    ): ?Log {
        $service = self::userActivity()->forEntity('specific_data', $specificDataId);
        
        if ($contractId) {
            $service->forContract($contractId);
        }

        return $service->info("Specific data {$action}", [
            'specific_data_id' => $specificDataId,
            'contract_id' => $contractId,
            'action' => $action,
            'changes' => $changes,
            'changed_fields' => array_keys($changes),
        ]);
    }

    /**
     * Quick log for external API calls (Google Sheets, etc.).
     */
    public static function logExternalApiCall(
        string $service,
        string $operation,
        bool $success = true,
        array $context = []
    ): ?Log {
        $logger = self::externalApi();
        
        $context['service'] = $service;
        $context['operation'] = $operation;

        if ($success) {
            return $logger->info("External API call: {$service} - {$operation}", $context);
        }

        return $logger->error("External API call failed: {$service} - {$operation}", $context);
    }

    /**
     * Quick log for database operation tracking (INSERT/UPDATE/DELETE).
     * Logs to the 'database' source with db_table and db_operation in context
     * for filtering in the frontend.
     *
     * Usage (from LogsDatabaseOperations trait):
     *   SystemLogService::logDbOperation('contracts', 'INSERT', $contract->id);
     *   SystemLogService::logDbOperation('users', 'UPDATE', $user->id, ['changed_fields' => ['name', 'email']]);
     *   SystemLogService::logDbOperation('leads', 'DELETE', $lead->id, [], 'warning');
     *
     * @param string   $table     Database table name (e.g. 'contracts', 'users')
     * @param string   $operation DB operation: 'INSERT', 'UPDATE', 'DELETE'
     * @param int|null $recordId  The ID of the affected record
     * @param array    $context   Additional context (changes, field names, etc.)
     * @param string   $level     Log level (default: 'info')
     */
    public static function logDbOperation(
        string $table,
        string $operation,
        ?int $recordId = null,
        array $context = [],
        string $level = 'info'
    ): ?Log {
        // Merge db-specific keys into context for frontend filtering
        $context['db_table'] = $table;
        $context['db_operation'] = strtoupper($operation);

        if ($recordId !== null) {
            $context['db_record_id'] = $recordId;
        }

        $message = strtoupper($operation) . " on `{$table}`"
            . ($recordId !== null ? " (ID: {$recordId})" : '');

        return self::database()->{$level}($message, $context);
    }
}