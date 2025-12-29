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
    protected $description = 'Automatically escalate priority for active tickets based on age';

    /**
     * Priority escalation thresholds (in days since creation)
     */
    private const PRIORITY_THRESHOLDS = [
        'high'   => 6,  // > 6 days → high priority
        'medium' => 4,  // > 4 days → medium priority
        'low'    => 1,  // >= 1 day → low priority
    ];

    /**
     * Priority weight for comparison (higher = more urgent)
     */
    private const PRIORITY_WEIGHT = [
        'unassigned' => 0,
        'low'        => 1,
        'medium'     => 2,
        'high'       => 3,
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
            'skipped' => 0,
            'errors' => 0,
        ];

        // Get ALL active tickets (Nuovi + In Lavorazione)
        $tickets = Ticket::whereIn('status', [Ticket::STATUS_NEW, Ticket::STATUS_WAITING])->get();

        $this->info("Found {$tickets->count()} active tickets (Nuovi + In Lavorazione)");

        foreach ($tickets as $ticket) {
            try {
                $daysSinceCreation = $ticket->created_at->diffInDays(now());
                $calculatedPriority = $this->calculatePriority($daysSinceCreation);

                // Skip if ticket is too new (< 2 days)
                if ($calculatedPriority === null) {
                    $stats['skipped']++;
                    continue;
                }

                // Skip if current priority is already >= calculated priority
                if (!$this->shouldEscalate($ticket->priority, $calculatedPriority)) {
                    $stats['skipped']++;
                    continue;
                }

                $this->line("  Ticket #{$ticket->ticket_number}: {$daysSinceCreation} days old, {$ticket->priority} → {$calculatedPriority}");

                if (!$isDryRun) {
                    $this->updateTicketPriority($ticket, $calculatedPriority);
                }

                $stats[$calculatedPriority]++;

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
        $this->line("  Skipped:         {$stats['skipped']}");
        
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
     * Calculate the appropriate priority based on days since creation
     * 
     * @param int $daysSinceCreation
     * @return string|null The calculated priority or null if too new
     */
    private function calculatePriority(int $daysSinceCreation): ?string
    {
        if ($daysSinceCreation > self::PRIORITY_THRESHOLDS['high']) {
            return Ticket::PRIORITY_HIGH;
        }
        
        if ($daysSinceCreation > self::PRIORITY_THRESHOLDS['medium']) {
            return Ticket::PRIORITY_MEDIUM;
        }
        
        if ($daysSinceCreation >= self::PRIORITY_THRESHOLDS['low']) {
            return Ticket::PRIORITY_LOW;
        }

        return null;
    }

    /**
     * Check if ticket should be escalated (new priority > current priority)
     * 
     * @param string $currentPriority
     * @param string $newPriority
     * @return bool
     */
    private function shouldEscalate(string $currentPriority, string $newPriority): bool
    {
        $currentWeight = self::PRIORITY_WEIGHT[$currentPriority] ?? 0;
        $newWeight = self::PRIORITY_WEIGHT[$newPriority] ?? 0;

        return $newWeight > $currentWeight;
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

            // Log the change with user_id = 1 (Admin/System)
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