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
            $table->text('macro_stato')->nullable();
            $table->text('fase')->nullable();
            $table->text('specifica')->nullable();
            $table->integer('genera_pv')->nullable();
            $table->integer('genera_pc')->nullable();
            $table->unsignedBigInteger('status_contract_id');
            $table->foreign('status_contract_id')->references('id')->on('status_contracts');
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
