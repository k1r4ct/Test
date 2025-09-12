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
        Schema::create('contract_type_informations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('macro_product_id');
            $table->foreign('macro_product_id')->references('id')->on('macro_products');
            $table->text('domanda');
            $table->text('tipo_risposta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_type_informations');
    }
};
