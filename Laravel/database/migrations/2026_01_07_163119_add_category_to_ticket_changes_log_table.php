<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds category tracking columns to ticket_changes_log table
     * to track category changes (ordinary/extraordinary).
     */
    public function up(): void
    {
        Schema::table('ticket_changes_log', function (Blueprint $table) {
            $table->string('previous_category')->nullable()->after('new_priority');
            $table->string('new_category')->nullable()->after('previous_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_changes_log', function (Blueprint $table) {
            $table->dropColumn(['previous_category', 'new_category']);
        });
    }
};