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
        Schema::create('survey_type_informations', function (Blueprint $table) {
            $table->id();
            $table->text('domanda');
            $table->decimal('risposta_tipo_numero',10,2);
            $table->text('risposta_tipo_stringa');
            $table->boolean('obbligatorio')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_type_informations');
    }
};
