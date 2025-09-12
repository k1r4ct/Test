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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->text('descrizione')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->integer('punti_valore')->nullable();
            $table->integer('punti_carriera')->nullable();
            $table->integer('attivo')->nullable();
            $table->unsignedBigInteger('macro_product_id');
            $table->foreign('macro_product_id')->references('id')->on('macro_products');
            $table->decimal('gettone',10,2)->nullable();
            $table->date('inizio_offerta')->nullable();
            $table->date('fine_offerta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
