<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates the change_type enum to include 'category' option
     */
    public function up(): void
    {
        // MySQL requires raw SQL to modify ENUM columns
        DB::statement("ALTER TABLE ticket_changes_log MODIFY COLUMN change_type ENUM('status', 'priority', 'both', 'category') DEFAULT 'status'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if any rows have 'category' as change_type
        DB::statement("ALTER TABLE ticket_changes_log MODIFY COLUMN change_type ENUM('status', 'priority', 'both') DEFAULT 'status'");
    }
};