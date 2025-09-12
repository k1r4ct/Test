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
        Schema::create('customer_datas', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
            $table->string('pec')->nullable();
            $table->string('codice_fiscale')->nullable();
            $table->string('telefono')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap')->nullable();
            $table->string('provincia')->nullable();
            $table->string('nazione')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_datas');
    }
};
