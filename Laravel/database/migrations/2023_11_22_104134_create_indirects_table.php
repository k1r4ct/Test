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
        Schema::create('indirects', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_livello');
            $table->integer('percentuale_indiretta');
            $table->unsignedBigInteger('qualification_id');
            $table->foreign('qualification_id')->references('id')->on('qualifications');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indirects');
    }
};
