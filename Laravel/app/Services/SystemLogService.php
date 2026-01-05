<?php

namespace App\Services;

use App\Models\Log;
use App\Models\LogSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log as LaravelLog;

class SystemLogService
{
    /**
     * Current log source context.
     *
     * @var string|null
     */
    protected ?string $source = null;

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
    public static function api(): self
    {
        return new self(Log::SOURCE_API);
    }

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

        return $this->createLog(Log::LEVEL_ERROR, $message, $context, $stackTrace);
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

        // Send notification for critical errors if enabled
        $this->notifyIfCritical($message, $context);

        return $log;
    }

    // ==================== GENERIC LOG METHOD ====================

    /**
     * Create a log entry with specified level and source.
     * 
     * @param string $level Log level (debug, info, warning, error, critical)
     * @param string $source Log source (auth, api, database, scheduler, email, system, user_activity)
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

        // Write to database if enabled
        if ($this->isLogToDatabaseEnabled()) {
            try {
                $log = Log::create([
                    'level' => $level,
                    'source' => $source,
                    'message' => $message,
                    'datetime' => now(),
                    'user_id' => $userId,
                    'context' => !empty($context) ? $context : null,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'request_url' => $requestUrl,
                    'request_method' => $requestMethod,
                    'stack_trace' => $stackTrace,
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
            Log::SOURCE_API => 'api',
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

    /**
     * Send notification for critical errors if enabled in settings.
     */
    protected function notifyIfCritical(string $message, array $context): void
    {
        try {
            $notifyEnabled = LogSetting::get('notify_critical_errors', false);
            
            if (!$notifyEnabled) {
                return;
            }

            $email = LogSetting::get('notify_email');
            
            if (!$email) {
                return;
            }

            // Queue email notification
            // You can implement this with your preferred mail system
            // Mail::to($email)->queue(new CriticalErrorNotification($message, $context));
            
        } catch (\Exception $e) {
            // Silently fail - don't break logging because notification failed
        }
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
        $service = self::auth();
        
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
        return self::auth()->info("User logged out", [
            'user_id' => $userId,
            'email' => $email,
        ]);
    }

    /**
     * Quick log for password change.
     */
    public static function logPasswordChange(int $userId, string $email): ?Log
    {
        return self::auth()->info("Password changed", [
            'user_id' => $userId,
            'email' => $email,
        ]);
    }

    /**
     * Quick log for API request.
     */
    public static function logApiRequest(string $endpoint, string $method, int $statusCode, ?float $duration = null): ?Log
    {
        $level = $statusCode >= 500 ? Log::LEVEL_ERROR : 
                ($statusCode >= 400 ? Log::LEVEL_WARNING : Log::LEVEL_INFO);

        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
        ];

        if ($duration !== null) {
            $context['duration_ms'] = round($duration * 1000, 2);
        }

        return self::log($level, Log::SOURCE_API, "API {$method} {$endpoint} - {$statusCode}", $context);
    }

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
     * Quick log for contract status change (backward compatibility).
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

        return self::userActivity()->info($message, [
            'contract_id' => $contractId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_name' => $userName,
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
}