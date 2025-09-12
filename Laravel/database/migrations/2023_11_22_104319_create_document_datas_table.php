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
        Schema::create('document_datas', function (Blueprint $table) {
            $table->id();
            $table->text('tipo');
            $table->text('descrizione');
            $table->text('path_storage');
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
        Schema::dropIfExists('document_datas');
    }
};
