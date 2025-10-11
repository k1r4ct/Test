<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the enum field to include 'status_change'
        DB::statement("ALTER TABLE ticket_messages MODIFY message_type ENUM('text', 'attachment', 'status_change') DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE ticket_messages MODIFY message_type ENUM('text', 'attachment') DEFAULT 'text'");
    }
};