<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds timestamp columns to track when tickets are archived (closed)
     * and when they are restored from closed/deleted status.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Timestamp for when the ticket was archived (moved to 'closed' status)
            $table->timestamp('closed_at')->nullable()->after('deleted_at');
            
            // Timestamp for when the ticket was restored from closed/deleted status
            $table->timestamp('restored_at')->nullable()->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['closed_at', 'restored_at']);
        });
    }
};
