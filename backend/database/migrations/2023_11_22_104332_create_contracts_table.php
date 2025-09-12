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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->text('codice_contratto')->nullable();
            $table->unsignedBigInteger('id_inserito_da');
            $table->foreign('id_inserito_da')->references('id')->on('users');
            $table->unsignedBigInteger('id_associato_a');
            $table->foreign('id_associato_a')->references('id')->on('users');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products');
            $table->unsignedBigInteger('customer_data_id');
            $table->foreign('customer_data_id')->references('id')->on('customer_datas');
            $table->unsignedBigInteger('specific_data_id');
            $table->foreign('specific_data_id')->references('id')->on('specific_datas');
            $table->date('data_inserimento')->nullable();
            $table->date('data_stipula')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('payment_mode_id');
            $table->foreign('payment_mode_id')->references('id')->on('payment_modes');
            $table->unsignedBigInteger('status_contract_id');
            $table->foreign('status_contract_id')->references('id')->on('status_contracts');
            $table->unsignedBigInteger('document_data_id');
            $table->foreign('document_data_id')->references('id')->on('document_datas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
