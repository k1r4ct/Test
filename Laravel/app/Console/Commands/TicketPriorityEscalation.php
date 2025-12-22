<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketChangeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketPriorityEscalation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:priority-escalation 
                            {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically escalate priority for tickets with unassigned priority based on age';

    /**
     * Priority escalation thresholds (in days since last update)
     */
    private const PRIORITY_THRESHOLDS = [
        'high'   => 6,  // > 6 days → high priority
        'medium' => 4,  // > 4 days → medium priority
        'low'    => 2,  // >= 2 days → low priority
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting ticket priority escalation...');

        $stats = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'errors' => 0,
        ];

        // Get tickets that are eligible for priority escalation:
        // - Status: new or waiting (active tickets only)
        // - Priority: unassigned (manual priorities are not touched)
        $tickets = Ticket::whereIn('status', [Ticket::STATUS_NEW, Ticket::STATUS_WAITING])
            ->where('priority', Ticket::PRIORITY_UNASSIGNED)
            ->get();

        $this->info("Found {$tickets->count()} tickets with unassigned priority");

        foreach ($tickets as $ticket) {
            try {
                $daysSinceUpdate = $ticket->updated_at->diffInDays(now());
                $newPriority = $this->calculatePriority($daysSinceUpdate);

                // Skip if no priority change needed
                if ($newPriority === null) {
                    continue;
                }

                $this->line("  Ticket #{$ticket->ticket_number}: {$daysSinceUpdate} days old → {$newPriority} priority");

                if (!$isDryRun) {
                    $this->updateTicketPriority($ticket, $newPriority);
                }

                $stats[$newPriority]++;

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Error escalating priority for ticket #{$ticket->ticket_number}: " . $e->getMessage());
                $this->error("  Error processing ticket #{$ticket->ticket_number}: " . $e->getMessage());
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Priority Escalation Summary ===');
        $this->line("  Low priority:    {$stats['low']}");
        $this->line("  Medium priority: {$stats['medium']}");
        $this->line("  High priority:   {$stats['high']}");
        
        if ($stats['errors'] > 0) {
            $this->error("  Errors: {$stats['errors']}");
        }

        $total = $stats['low'] + $stats['medium'] + $stats['high'];
        
        if ($isDryRun) {
            $this->warn("DRY RUN: {$total} tickets would have been updated");
        } else {
            $this->info("Successfully updated {$total} tickets");
            Log::info("Ticket priority escalation completed: {$total} tickets updated", $stats);
        }

        return Command::SUCCESS;
    }

    /**
     * Calculate the appropriate priority based on days since last update
     * 
     * @param int $daysSinceUpdate
     * @return string|null The new priority or null if no change needed
     */
    private function calculatePriority(int $daysSinceUpdate): ?string
    {
        // Check thresholds in order (highest first)
        if ($daysSinceUpdate > self::PRIORITY_THRESHOLDS['high']) {
            return Ticket::PRIORITY_HIGH;
        }
        
        if ($daysSinceUpdate > self::PRIORITY_THRESHOLDS['medium']) {
            return Ticket::PRIORITY_MEDIUM;
        }
        
        if ($daysSinceUpdate >= self::PRIORITY_THRESHOLDS['low']) {
            return Ticket::PRIORITY_LOW;
        }

        // Less than 2 days - no priority assigned yet
        return null;
    }

    /**
     * Update ticket priority and log the change
     * 
     * @param Ticket $ticket
     * @param string $newPriority
     */
    private function updateTicketPriority(Ticket $ticket, string $newPriority): void
    {
        DB::beginTransaction();

        try {
            $oldPriority = $ticket->priority;

            // Update ticket priority (without touching updated_at)
            $ticket->timestamps = false;
            $ticket->priority = $newPriority;
            $ticket->save();
            $ticket->timestamps = true;

            // Log the change with user_id = null to indicate automatic system change
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1,
                'previous_status' => null,
                'new_status' => null,
                'previous_priority' => $oldPriority,
                'new_priority' => $newPriority,
                'change_type' => TicketChangeLog::CHANGE_TYPE_PRIORITY,
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
