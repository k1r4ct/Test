<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\LogSetting;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * 
     * These commands handle automatic system maintenance:
     * 
     * 1. Priority Escalation (hourly):
     *    - Tickets in 'new' or 'waiting' with 'unassigned' priority
     *    - >= 2 days → low, > 4 days → medium, > 6 days → high
     * 
     * 2. Ticket Cleanup (daily at 02:00):
     *    - resolved > 10 days → closed
     *    - closed > 10 days → deleted
     *    - deleted > 40 days → permanently removed from DB
     * 
     * 3. Log Cleanup (configurable, default daily at 03:00):
     *    - Removes old logs based on retention settings per source
     *    - Cleans both database records and log files
     */
    protected function schedule(Schedule $schedule): void
    {
        // Priority escalation - runs every hour
        // Escalates priority for tickets with 'unassigned' priority based on age
        $schedule->command('tickets:priority-escalation')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ticket-priority-escalation.log'));

        // Ticket cleanup - runs daily at 2:00 AM
        // Moves tickets through lifecycle: resolved→closed→deleted→removed
        $schedule->command('tickets:cleanup')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ticket-cleanup.log'));

        // Log cleanup - runs based on settings (default: daily at 03:00)
        // Removes old log entries based on retention settings per source
        $this->scheduleLogCleanup($schedule);
    }

    /**
     * Schedule log cleanup based on database settings
     */
    protected function scheduleLogCleanup(Schedule $schedule): void
    {
        // Get cleanup settings with fallback defaults
        try {
            $enabled = LogSetting::get('cleanup_enabled', true);
            $frequency = LogSetting::get('cleanup_frequency', 'daily');
            $time = LogSetting::get('cleanup_time', '03:00');
        } catch (\Exception $e) {
            // If settings table doesn't exist yet, use defaults
            $enabled = true;
            $frequency = 'daily';
            $time = '03:00';
        }

        if (!$enabled) {
            return;
        }

        $command = $schedule->command('logs:cleanup')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/logs-cleanup.log'));

        // Set frequency based on settings
        switch ($frequency) {
            case 'daily':
                $command->dailyAt($time);
                break;
            case 'weekly':
                $command->weeklyOn(0, $time); // Sunday
                break;
            case 'monthly':
                $command->monthlyOn(1, $time); // First day of month
                break;
            default:
                $command->dailyAt($time);
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}