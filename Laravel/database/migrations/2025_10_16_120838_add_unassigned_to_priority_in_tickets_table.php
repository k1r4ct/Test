<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'unassigned' option to priority enum
     * Change default from 'medium' to 'unassigned'
     */
    public function up(): void
    {
        // Modify the priority enum to include 'unassigned' and change default
        DB::statement("ALTER TABLE tickets MODIFY COLUMN priority ENUM('low','medium','high','unassigned') NOT NULL DEFAULT 'unassigned'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any 'unassigned' values to 'medium' before removing the option
        DB::statement("UPDATE tickets SET priority = 'medium' WHERE priority = 'unassigned'");
        
        // Revert to original enum without 'unassigned'
        DB::statement("ALTER TABLE tickets MODIFY COLUMN priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium'");
    }
};