<?php

namespace App\Listeners;

use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Events\MigrationEnded;
use App\Services\SystemLogService;

class MigrationEventsListener
{
    /**
     * Handle migration completed (up or down).
     */
    public function handleMigrationEnded(MigrationEnded $event): void
    {
        $migration = $event->migration;
        $method = $event->method; // 'up' or 'down'

        // Determine operation: up = INSERT (new migration), down = DELETE (rollback)
        $operation = $method === 'up' ? 'INSERT' : 'DELETE';
        $level = $method === 'up' ? 'info' : 'warning';

        // Get migration class name for readable logging
        $migrationName = get_class($migration) !== 'class@anonymous'
            ? get_class($migration)
            : basename($this->getMigrationFileName($migration));

        SystemLogService::logDbOperation(
            'migrations',
            $operation,
            null,
            [
                'migration' => $migrationName,
                'method' => $method,
            ],
            $level
        );
    }

    /**
     * Try to extract the migration file name from the migration object.
     *
     * Anonymous classes (used in modern Laravel migrations) include
     * the file path in their class name, so we can extract it.
     */
    protected function getMigrationFileName($migration): string
    {
        // For anonymous classes, the class name contains the file path
        $className = get_class($migration);

        // Anonymous class names look like: class@anonymousC:\path\to\migration.php$0x...
        if (str_contains($className, '@anonymous')) {
            // Extract just the file path portion
            $path = preg_replace('/^class@anonymous/', '', $className);
            $path = preg_replace('/\$.*$/', '', $path);
            return $path ?: 'unknown';
        }

        return $className;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     * @return array<string, string>
     */
    public function subscribe($events): array
    {
        return [
            MigrationEnded::class => 'handleMigrationEnded',
        ];
    }
}