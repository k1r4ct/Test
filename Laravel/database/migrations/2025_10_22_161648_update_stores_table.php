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
        Schema::table('stores', function (Blueprint $table) {
            $table->enum('store_type', ['digital', 'physical'])->default('digital')->after('store_name');
            
            $table->unsignedBigInteger('filter_id')->nullable()->after('store_type');
            $table->foreign('filter_id')->references('id')->on('filters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('store_type');
            
            $table->dropForeign(['filter_id']);
            $table->dropColumn('filter_id');
        });
    }
};