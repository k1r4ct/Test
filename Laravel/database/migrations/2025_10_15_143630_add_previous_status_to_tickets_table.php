<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add previous_status column to tickets table
     * This stores the previous status before a status change
     * Used for generating proper color gradients in the UI
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add previous_status after status column
            $table->string('previous_status')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('previous_status');
        });
    }
};