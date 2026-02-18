<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix the 'note' column type in leads table.
     * It was incorrectly set as TIME instead of TEXT.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->text('note')->nullable()->change();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->time('note')->nullable()->change();
        });
    }
};