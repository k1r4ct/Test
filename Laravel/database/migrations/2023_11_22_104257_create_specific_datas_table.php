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
        Schema::create('specific_datas', function (Blueprint $table) {
            $table->id();
            $table->text('domanda');
            $table->decimal('risposta_tipo_numero',10,2)->nullable();
            $table->text('risposta_tipo_stringa')->nullable();
            $table->boolean('risposta_tipo_bool')->nullable();
            $table->varchar('risposta_tipo_data',100)->nullable();
            $table->unsignedBigInteger('contract_id');
            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specific_datas');
    }
};
