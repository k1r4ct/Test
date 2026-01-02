<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log;
use App\Models\LogSetting;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanOldLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup 
                            {--dry-run : Run without actually deleting anything}
                            {--source= : Clean only a specific source}
                            {--force : Force cleanup even if disabled in settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old log entries based on retention settings';

    /**
     * Log sources and their settings keys
     */
    private array $sources = [
        'auth' => 'retention_auth',
        'api' => 'retention_api',
        'database' => 'retention_database',
        'scheduler' => 'retention_scheduler',
        'email' => 'retention_email',
        'system' => 'retention_system',
        'user_activity' => 'retention_user_activity',
        'external_api' => 'retention_external_api',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Log Cleanup Started ===');
        $this->newLine();

        // Check if cleanup is enabled
        if (!$this->option('force') && !LogSetting::isCleanupEnabled()) {
            $this->warn('Cleanup is disabled in settings. Use --force to override.');
            return Command::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
            $this->newLine();
        }

        $specificSource = $this->option('source');
        $totalDeleted = 0;
        $stats = [];

        // Process each source
        foreach ($this->sources as $source => $settingKey) {
            // Skip if specific source requested and this isn't it
            if ($specificSource && $source !== $specificSource) {
                continue;
            }

            $retentionDays = LogSetting::get($settingKey, 30);
            $cutoffDate = Carbon::now()->subDays($retentionDays);

            $this->info("Processing: {$source}");
            $this->line("  Retention: {$retentionDays} days");
            $this->line("  Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");

            // Count records to delete
            $count = Log::where('source', $source)
                ->where('created_at', '<', $cutoffDate)
                ->count();

            $this->line("  Records to delete: {$count}");

            if ($count > 0 && !$isDryRun) {
                // Delete in chunks to avoid memory issues
                $deleted = $this->deleteInChunks($source, $cutoffDate);
                $totalDeleted += $deleted;
                $stats[$source] = $deleted;
                $this->info("  ✓ Deleted {$deleted} records");
            } elseif ($count > 0) {
                $stats[$source] = $count;
                $this->line("  Would delete {$count} records");
            } else {
                $stats[$source] = 0;
                $this->line("  Nothing to delete");
            }

            $this->newLine();
        }

        // Clean old log files
        $this->info('Cleaning old log files...');
        $filesDeleted = $this->cleanOldLogFiles($isDryRun);
        $this->line("  Log files cleaned: {$filesDeleted}");
        $this->newLine();

        // Update last run time
        if (!$isDryRun) {
            LogSetting::updateLastCleanupRun();
        }

        // Summary
        $this->info('=== Cleanup Summary ===');
        $this->table(
            ['Source', 'Records Deleted'],
            collect($stats)->map(fn($count, $source) => [$source, $count])->toArray()
        );
        $this->newLine();
        $this->info("Total database records deleted: {$totalDeleted}");
        $this->info("Total log files cleaned: {$filesDeleted}");

        // Log the cleanup
        if (!$isDryRun) {
            SystemLogService::system()->info('Automatic log cleanup completed', [
                'total_deleted' => $totalDeleted,
                'files_deleted' => $filesDeleted,
                'stats' => $stats,
            ]);
        }

        $this->newLine();
        $this->info('=== Log Cleanup Completed ===');

        return Command::SUCCESS;
    }

    /**
     * Delete records in chunks to avoid memory issues
     */
    private function deleteInChunks(string $source, Carbon $cutoffDate): int
    {
        $totalDeleted = 0;
        $chunkSize = 1000;

        do {
            $deleted = Log::where('source', $source)
                ->where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $deleted;

        } while ($deleted === $chunkSize);

        return $totalDeleted;
    }

    /**
     * Clean old log files from storage/logs
     */
    private function cleanOldLogFiles(bool $isDryRun): int
    {
        $logPath = storage_path('logs');
        $filesDeleted = 0;

        if (!is_dir($logPath)) {
            return 0;
        }

        $files = glob($logPath . '/*.log');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip current day's files and the main laravel.log
            if ($filename === 'laravel.log') {
                continue;
            }

            // Get file modification time
            $fileTime = filemtime($file);
            $fileAge = Carbon::createFromTimestamp($fileTime);
            
            // Determine retention based on file name
            $retentionDays = $this->getFileRetentionDays($filename);
            $cutoffDate = Carbon::now()->subDays($retentionDays);

            if ($fileAge->lt($cutoffDate)) {
                $this->line("  - {$filename} (age: {$fileAge->diffInDays()} days)");
                
                if (!$isDryRun) {
                    // Truncate file instead of deleting to maintain log rotation
                    file_put_contents($file, '');
                    $filesDeleted++;
                }
            }
        }

        return $filesDeleted;
    }

    /**
     * Get retention days for a log file based on its name
     */
    private function getFileRetentionDays(string $filename): int
    {
        $sourceMap = [
            'auth.log' => 'retention_auth',
            'api.log' => 'retention_api',
            'database.log' => 'retention_database',
            'scheduler.log' => 'retention_scheduler',
            'email.log' => 'retention_email',
            'system.log' => 'retention_system',
            'user_activity.log' => 'retention_user_activity',
            'external_api.log' => 'retention_external_api',
        ];

        $settingKey = $sourceMap[$filename] ?? 'retention_system';
        return LogSetting::get($settingKey, 30);
    }
}
