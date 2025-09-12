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
        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicabile_da_role_id');
            $table->foreign('applicabile_da_role_id')->references('id')->on('roles');
            $table->text('micro_stato');
            $table->text('macro_stato');
            $table->text('fase');
            $table->text('specifica');
            $table->unsignedBigInteger('color_id');
            $table->foreign('color_id')->references('id')->on('table_colors');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_statuses');
    }
};
