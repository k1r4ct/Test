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
        Schema::create('option_status_contracts', function (Blueprint $table) {
            $table->id();
            $table->text('macro_stato');
            $table->text('fase');
            $table->text('specifica');
            $table->integer('genera_pv');
            $table->integer('genera_pc');
            $table->unsignedBigInteger('status_contract_id');
            $table->foreign('status_contract_id')->references('id')->on('status_contracts');
            $table->unsignedBigInteger('applicabile_da_role_id');
            $table->foreign('applicabile_da_role_id')->references('id')->on('roles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_status_contracts');
    }
};
