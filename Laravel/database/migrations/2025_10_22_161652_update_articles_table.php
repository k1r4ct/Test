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
        Schema::table('articles', function (Blueprint $table) {
            // Remove filter_id (filters apply via category and store)
            $table->dropForeign(['filter_id']);
            $table->dropColumn('filter_id');
            
            // Add is_digital flag
            $table->boolean('is_digital')->default(true)->after('pv_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('is_digital');
            
            // Re-add filter_id
            $table->unsignedBigInteger('filter_id')->after('description');
            $table->foreign('filter_id')->references('id')->on('filters');
        });
    }
};