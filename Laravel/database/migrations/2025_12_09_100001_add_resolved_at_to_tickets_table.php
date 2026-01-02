<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds resolved_at timestamp to track when tickets enter 'resolved' status.
     * This is needed for the automatic cleanup automation that moves:
     * - resolved (>10 days) → closed
     * - closed (>10 days) → deleted  
     * - deleted (>40 days) → permanently removed
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Timestamp for when the ticket was resolved
            $table->timestamp('resolved_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });
    }
};
