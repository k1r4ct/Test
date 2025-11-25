<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Backfills the closed_at column for tickets that are already in 'closed' status.
     * Uses the ticket_changes_log table to find when each ticket was moved to 'closed'.
     * Falls back to updated_at if no log entry exists.
     */
    public function up(): void
    {
        // First, update tickets that have a log entry for 'closed' status
        DB::statement("
            UPDATE `tickets` t
            INNER JOIN (
                SELECT 
                    ticket_id,
                    MAX(created_at) as closed_timestamp
                FROM `ticket_changes_log`
                WHERE new_status = 'closed'
                GROUP BY ticket_id
            ) log ON t.id = log.ticket_id
            SET t.closed_at = log.closed_timestamp
            WHERE t.status = 'closed'
            AND t.closed_at IS NULL
        ");

        // For any remaining closed tickets without a log entry, use updated_at as fallback
        DB::statement("
            UPDATE `tickets`
            SET closed_at = updated_at
            WHERE status = 'closed'
            AND closed_at IS NULL
        ");

        // Log how many records were updated
        $updatedCount = DB::table('tickets')
            ->where('status', 'closed')
            ->whereNotNull('closed_at')
            ->count();
            
        if ($updatedCount > 0) {
            \Log::info("Backfilled closed_at for {$updatedCount} tickets");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all closed_at values
        DB::statement("UPDATE `tickets` SET `closed_at` = NULL");
    }
};
