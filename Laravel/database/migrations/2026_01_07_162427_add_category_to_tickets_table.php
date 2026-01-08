<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds a 'category' column to the tickets table for categorizing tickets
     * as 'ordinary' (default) or 'extraordinary'.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('category', ['ordinary', 'extraordinary'])
                  ->default('ordinary')
                  ->after('priority')
                  ->comment('Ticket category: ordinary or extraordinary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};