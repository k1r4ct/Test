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
        // Update any existing 'in-progress' tickets to 'waiting'
        DB::table('tickets')
            ->where('status', 'in-progress')
            ->update(['status' => 'waiting']);
        
        // Modify ENUM to remove 'in-progress' and add 'deleted'
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('new', 'waiting', 'resolved', 'deleted') DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Move any 'deleted' tickets to 'resolved' before removing the enum value
        DB::table('tickets')
            ->where('status', 'deleted')
            ->update(['status' => 'resolved']);
        
        // Restore the old ENUM with 'in-progress'
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('new', 'in-progress', 'waiting', 'resolved') DEFAULT 'new'");
    }
};