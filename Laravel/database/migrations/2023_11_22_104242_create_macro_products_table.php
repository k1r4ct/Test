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
        Schema::create('macro_products', function (Blueprint $table) {
            $table->id();
            $table->text('codice_macro');
            $table->text('descrizione');
            $table->integer('punti_valore');
            $table->integer('punti_carriera');
            $table->unsignedBigInteger('supplier_category_id');
            $table->foreign('supplier_category_id')->references('id')->on('supplier_categories');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('macro_products');
    }
};
