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
        // Add 'closed' to the status enum
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('new', 'waiting', 'resolved', 'closed', 'deleted') DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Move any 'closed' tickets to 'resolved' before removing the enum value
        DB::table('tickets')
            ->where('status', 'closed')
            ->update(['status' => 'resolved']);
        
        // Remove 'closed' from enum
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('new', 'waiting', 'resolved', 'deleted') DEFAULT 'new'");
    }
};