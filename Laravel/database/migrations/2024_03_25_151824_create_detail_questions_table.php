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
        Schema::create('detail_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_type_information_id');
            $table->foreign('contract_type_information_id')->references('id')->on('contract_type_informations');
            $table->text('opzione')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_questions');
    }
};
