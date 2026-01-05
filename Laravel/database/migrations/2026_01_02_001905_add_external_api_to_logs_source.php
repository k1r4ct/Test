<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds 'external_api' to the source ENUM column for tracking
     * external API integrations like Google Sheets.
     */
    public function up(): void
    {
        // Modify the ENUM to include 'external_api'
        DB::statement("
            ALTER TABLE `logs` 
            MODIFY COLUMN `source` ENUM(
                'auth',
                'api', 
                'database',
                'scheduler',
                'email',
                'system',
                'user_activity',
                'external_api'
            ) DEFAULT 'user_activity'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, change any 'external_api' records to 'api' before removing the enum value
        DB::table('logs')
            ->where('source', 'external_api')
            ->update(['source' => 'api']);

        // Revert to original ENUM without 'external_api'
        DB::statement("
            ALTER TABLE `logs` 
            MODIFY COLUMN `source` ENUM(
                'auth',
                'api',
                'database',
                'scheduler',
                'email',
                'system',
                'user_activity'
            ) DEFAULT 'user_activity'
        ");
    }
};