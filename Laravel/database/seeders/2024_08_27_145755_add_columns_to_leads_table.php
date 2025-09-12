<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->date('data_appuntamento')->nullable(); // Aggiunge la colonna data_appuntamento (può essere NULL)
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('data_appuntamento'); // Rimuove la colonna data_appuntamento
            $table->dropColumn('note');
        });
    }
};
