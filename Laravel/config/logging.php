<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    | Custom Channels (Semprechiaro CRM):
    | - auth: Authentication events (login, logout, password changes)
    | - api: API requests and responses
    | - database: Database queries, slow queries, errors
    | - scheduler: Scheduled tasks and cron jobs
    | - email: Email sending events
    | - system: General system events
    | - user_activity: User actions (contract changes, etc.)
    | - external_api: External API calls (Google Sheets integration, etc.)
    |
    */

    'channels' => [

        /*
        |--------------------------------------------------------------------------
        | Stack Channel (Default)
        |--------------------------------------------------------------------------
        | Combines multiple channels. By default writes to single file.
        */
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Single File Channel (Laravel default)
        |--------------------------------------------------------------------------
        | All logs go to a single laravel.log file
        */
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Daily Rotation Channel (optional, not used by default)
        |--------------------------------------------------------------------------
        | Creates a new log file each day, keeps last 14 days
        */
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | SEMPRECHIARO CUSTOM CHANNELS
        |--------------------------------------------------------------------------
        | Single file per source - NO daily rotation
        | La pulizia viene gestita dal comando CleanOldLogs in base alle settings
        */

        // Authentication logs (login, logout, password changes, failed attempts)
        'auth' => [
            'driver' => 'single',
            'path' => storage_path('logs/auth.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // API logs (requests, responses, errors)
        'api' => [
            'driver' => 'single',
            'path' => storage_path('logs/api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // Database logs (queries, slow queries, connection errors)
        'database' => [
            'driver' => 'single',
            'path' => storage_path('logs/database.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // Scheduler logs (cron jobs, scheduled tasks)
        'scheduler' => [
            'driver' => 'single',
            'path' => storage_path('logs/scheduler.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // Email logs (sent emails, failures)
        'email' => [
            'driver' => 'single',
            'path' => storage_path('logs/email.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // System logs (general system events, errors)
        'system' => [
            'driver' => 'single',
            'path' => storage_path('logs/system.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // User activity logs (contract changes, user actions)
        'user_activity' => [
            'driver' => 'single',
            'path' => storage_path('logs/user_activity.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // External API logs (Google Sheets integration, external services)
        'external_api' => [
            'driver' => 'single',
            'path' => storage_path('logs/external_api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // Errors only - aggregates all errors from any source
        'errors' => [
            'driver' => 'single',
            'path' => storage_path('logs/errors.log'),
            'level' => 'error',
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | STANDARD LARAVEL CHANNELS
        |--------------------------------------------------------------------------
        */

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Semprechiaro CRM',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];