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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invitato_da_user_id');
            $table->foreign('invitato_da_user_id')->references('id')->on('users');
            $table->text('nome');
            $table->text('cognome');
            $table->text('telefono');
            $table->text('email');
            $table->unsignedBigInteger('lead_status_id');
            $table->foreign('lead_status_id')->references('id')->on('lead_statuses');
            $table->unsignedBigInteger('assegnato_a');
            $table->foreign('assegnato_a')->references('id')->on('users');
            $table->dateTime('data_appuntamento')->nullable();
            $table->time('ora_appuntamento')->nullable();
            $table->time('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
