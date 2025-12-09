<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Backfills the resolved_at column for tickets that are already in 'resolved' status
     * or have passed through 'resolved' status (now in 'closed' or 'deleted').
     * Uses the ticket_changes_log table to find when each ticket was moved to 'resolved'.
     * Falls back to updated_at if no log entry exists.
     */
    public function up(): void
    {
        // First, update tickets that have a log entry for 'resolved' status
        DB::statement("
            UPDATE `tickets` t
            INNER JOIN (
                SELECT 
                    ticket_id,
                    MAX(created_at) as resolved_timestamp
                FROM `ticket_changes_log`
                WHERE new_status = 'resolved'
                GROUP BY ticket_id
            ) log ON t.id = log.ticket_id
            SET t.resolved_at = log.resolved_timestamp
            WHERE t.status IN ('resolved', 'closed', 'deleted')
            AND t.resolved_at IS NULL
        ");

        // For any remaining resolved/closed/deleted tickets without a log entry, use updated_at as fallback
        DB::statement("
            UPDATE `tickets`
            SET resolved_at = updated_at
            WHERE status IN ('resolved', 'closed', 'deleted')
            AND resolved_at IS NULL
        ");

        // Log how many records were updated
        $updatedCount = DB::table('tickets')
            ->whereIn('status', ['resolved', 'closed', 'deleted'])
            ->whereNotNull('resolved_at')
            ->count();
            
        if ($updatedCount > 0) {
            Log::info("Backfilled resolved_at for {$updatedCount} tickets");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all resolved_at values
        DB::statement("UPDATE `tickets` SET `resolved_at` = NULL");
    }
};
