<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds 'removed' value to the status enums in ticket_changes_log table.
     * This status is used to track when a ticket is permanently deleted from the database.
     */
    public function up(): void
    {
        // Modify previous_status enum to include 'removed'
        DB::statement("ALTER TABLE `ticket_changes_log` 
            MODIFY COLUMN `previous_status` 
            ENUM('new', 'waiting', 'resolved', 'closed', 'deleted', 'removed') 
            DEFAULT NULL");

        // Modify new_status enum to include 'removed'
        DB::statement("ALTER TABLE `ticket_changes_log` 
            MODIFY COLUMN `new_status` 
            ENUM('new', 'waiting', 'resolved', 'closed', 'deleted', 'removed') 
            DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if there are any records with 'removed' status
        // You should delete those records first or handle them appropriately
        
        DB::statement("ALTER TABLE `ticket_changes_log` 
            MODIFY COLUMN `previous_status` 
            ENUM('new', 'waiting', 'resolved', 'closed', 'deleted') 
            DEFAULT NULL");

        DB::statement("ALTER TABLE `ticket_changes_log` 
            MODIFY COLUMN `new_status` 
            ENUM('new', 'waiting', 'resolved', 'closed', 'deleted') 
            DEFAULT NULL");
    }
};
