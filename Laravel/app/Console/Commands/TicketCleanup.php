<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketChangeLog;
use App\Models\TicketMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:cleanup 
                            {--dry-run : Run without making changes}
                            {--skip-resolved : Skip resolved → closed transition}
                            {--skip-closed : Skip closed → deleted transition}
                            {--skip-deleted : Skip permanent deletion of deleted tickets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically move old tickets through lifecycle: resolved→closed→deleted→removed';

    /**
     * Cleanup thresholds (in days)
     */
    private const RESOLVED_TO_CLOSED_DAYS = 10;
    private const CLOSED_TO_DELETED_DAYS = 10;
    private const DELETED_TO_REMOVED_DAYS = 40;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting ticket cleanup process...');
        $this->newLine();

        $stats = [
            'resolved_to_closed' => 0,
            'closed_to_deleted' => 0,
            'permanently_removed' => 0,
            'attachments_deleted' => 0,
            'errors' => 0,
        ];

        // Step 1: Move resolved tickets to closed (after 10 days)
        if (!$this->option('skip-resolved')) {
            $stats = $this->processResolvedTickets($stats, $isDryRun);
        }

        // Step 2: Move closed tickets to deleted (after 10 days)
        if (!$this->option('skip-closed')) {
            $stats = $this->processClosedTickets($stats, $isDryRun);
        }

        // Step 3: Permanently delete old deleted tickets (after 40 days)
        if (!$this->option('skip-deleted')) {
            $stats = $this->processDeletedTickets($stats, $isDryRun);
        }

        // Summary
        $this->newLine();
        $this->info('=== Cleanup Summary ===');
        $this->line("  Resolved → Closed:    {$stats['resolved_to_closed']}");
        $this->line("  Closed → Deleted:     {$stats['closed_to_deleted']}");
        $this->line("  Permanently removed:  {$stats['permanently_removed']}");
        $this->line("  Attachments deleted:  {$stats['attachments_deleted']}");
        
        if ($stats['errors'] > 0) {
            $this->error("  Errors: {$stats['errors']}");
        }

        $total = $stats['resolved_to_closed'] + $stats['closed_to_deleted'] + $stats['permanently_removed'];
        
        if ($isDryRun) {
            $this->warn("DRY RUN: {$total} tickets would have been processed");
        } else {
            $this->info("Successfully processed {$total} tickets");
            Log::info("Ticket cleanup completed", $stats);
        }

        return Command::SUCCESS;
    }

    /**
     * Process resolved tickets: move to closed after threshold
     */
    private function processResolvedTickets(array $stats, bool $isDryRun): array
    {
        $this->info('Step 1: Processing RESOLVED tickets (>' . self::RESOLVED_TO_CLOSED_DAYS . ' days → CLOSED)');

        $threshold = now()->subDays(self::RESOLVED_TO_CLOSED_DAYS);

        $tickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->where('resolved_at', '<=', $threshold)
            ->get();

        $this->line("  Found {$tickets->count()} tickets to process");

        foreach ($tickets as $ticket) {
            try {
                $daysResolved = $ticket->resolved_at->diffInDays(now());
                $this->line("    Ticket #{$ticket->ticket_number}: resolved {$daysResolved} days ago → closing");

                if (!$isDryRun) {
                    $this->moveTicketToStatus($ticket, Ticket::STATUS_CLOSED, 'closed_at');
                }

                $stats['resolved_to_closed']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Error moving ticket #{$ticket->ticket_number} to closed: " . $e->getMessage());
                $this->error("    Error: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Process closed tickets: move to deleted after threshold
     */
    private function processClosedTickets(array $stats, bool $isDryRun): array
    {
        $this->newLine();
        $this->info('Step 2: Processing CLOSED tickets (>' . self::CLOSED_TO_DELETED_DAYS . ' days → DELETED)');

        $threshold = now()->subDays(self::CLOSED_TO_DELETED_DAYS);

        $tickets = Ticket::where('status', Ticket::STATUS_CLOSED)
            ->where('closed_at', '<=', $threshold)
            ->get();

        $this->line("  Found {$tickets->count()} tickets to process");

        foreach ($tickets as $ticket) {
            try {
                $daysClosed = $ticket->closed_at->diffInDays(now());
                $this->line("    Ticket #{$ticket->ticket_number}: closed {$daysClosed} days ago → deleting");

                if (!$isDryRun) {
                    $this->moveTicketToStatus($ticket, Ticket::STATUS_DELETED, 'deleted_at');
                }

                $stats['closed_to_deleted']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Error moving ticket #{$ticket->ticket_number} to deleted: " . $e->getMessage());
                $this->error("    Error: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Process deleted tickets: permanently remove after threshold
     */
    private function processDeletedTickets(array $stats, bool $isDryRun): array
    {
        $this->newLine();
        $this->info('Step 3: Processing DELETED tickets (>' . self::DELETED_TO_REMOVED_DAYS . ' days → PERMANENTLY REMOVED)');

        $threshold = now()->subDays(self::DELETED_TO_REMOVED_DAYS);

        $tickets = Ticket::where('status', Ticket::STATUS_DELETED)
            ->where('deleted_at', '<=', $threshold)
            ->with('attachments')
            ->get();

        $this->line("  Found {$tickets->count()} tickets to permanently remove");

        foreach ($tickets as $ticket) {
            try {
                $daysDeleted = $ticket->deleted_at->diffInDays(now());
                $attachmentCount = $ticket->attachments->count();
                
                $this->line("    Ticket #{$ticket->ticket_number}: deleted {$daysDeleted} days ago, {$attachmentCount} attachments → removing");

                if (!$isDryRun) {
                    $attachmentsDeleted = $this->permanentlyRemoveTicket($ticket);
                    $stats['attachments_deleted'] += $attachmentsDeleted;
                } else {
                    $stats['attachments_deleted'] += $attachmentCount;
                }

                $stats['permanently_removed']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Error permanently removing ticket #{$ticket->ticket_number}: " . $e->getMessage());
                $this->error("    Error: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Move ticket to a new status with timestamp
     */
    private function moveTicketToStatus(Ticket $ticket, string $newStatus, string $timestampField): void
    {
        DB::beginTransaction();

        try {
            $oldStatus = $ticket->status;

            // Update ticket
            $ticket->update([
                'previous_status' => $oldStatus,
                'status' => $newStatus,
                $timestampField => now(),
            ]);

            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'previous_priority' => null,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS,
            ]);

            // Create system message
            $statusLabels = Ticket::getStatusOptions();
            $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            $newLabel = $statusLabels[$newStatus] ?? $newStatus;

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1,
                'message' => "Ticket spostato automaticamente da '{$oldLabel}' a '{$newLabel}' per inattività",
                'message_type' => 'status_change',
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Permanently remove a ticket and its attachments from the database
     */
    private function permanentlyRemoveTicket(Ticket $ticket): int
    {
        DB::beginTransaction();

        try {
            $attachmentCount = $ticket->attachments->count();

            // Log the permanent removal before deleting
            TicketChangeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1,
                'previous_status' => Ticket::STATUS_DELETED,
                'new_status' => TicketChangeLog::STATUS_REMOVED, // 'removed' status
                'previous_priority' => $ticket->priority,
                'new_priority' => null,
                'change_type' => TicketChangeLog::CHANGE_TYPE_STATUS,
            ]);

            // Delete attachments (the model's boot method handles file deletion)
            // This will automatically delete physical files due to TicketAttachment::boot()
            $ticket->attachments()->each(function ($attachment) {
                $attachment->delete();
            });

            // Delete messages
            $ticket->messages()->delete();

            // Delete change logs for this ticket (optional - you might want to keep them)
            // Uncomment the next line if you want to delete logs too:
            // TicketChangeLog::where('ticket_id', $ticket->id)->delete();

            // Finally, delete the ticket
            $ticket->delete();

            DB::commit();

            Log::info("Permanently removed ticket #{$ticket->ticket_number} with {$attachmentCount} attachments");

            return $attachmentCount;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
