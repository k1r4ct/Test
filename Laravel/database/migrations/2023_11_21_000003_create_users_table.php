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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('qualification_id');
            $table->foreign('qualification_id')->references('id')->on('qualifications');
            $table->string('codice')->nullable();
            $table->string('name');
            $table->string('cognome');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('pec')->nullable();
            $table->string('password');
            $table->string('codice_fiscale')->unique()->nullable();
            $table->string('telefono')->nullable();
            $table->string('cellulare')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap')->nullable();
            $table->string('provincia')->nullable();
            $table->string('nazione')->nullable();
            $table->integer('stato_user')->nullable();
            $table->integer('punti_valore_maturati')->nullable();
            $table->integer('punti_carriera_maturati')->nullable();
            $table->integer('user_id_padre')->nullable();
            $table->text('ragione_sociale')->nullable();
            $table->text('partita_iva')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
