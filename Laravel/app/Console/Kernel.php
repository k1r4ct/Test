<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * 
     * These commands handle automatic ticket lifecycle management:
     * 
     * 1. Priority Escalation (hourly):
     *    - Tickets in 'new' or 'waiting' with 'unassigned' priority
     *    - >= 2 days → low, > 4 days → medium, > 6 days → high
     * 
     * 2. Ticket Cleanup (daily at 02:00):
     *    - resolved > 10 days → closed
     *    - closed > 10 days → deleted
     *    - deleted > 40 days → permanently removed from DB
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