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
        Schema::table('filters', function (Blueprint $table) {
            // Remove store_id (filters apply to categories and stores, not directly)
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
            
            // Add role and qualification filters
            $table->unsignedBigInteger('role_id')->nullable()->after('description');
            $table->foreign('role_id')->references('id')->on('roles');
            
            $table->unsignedBigInteger('qualification_id')->nullable()->after('role_id');
            $table->foreign('qualification_id')->references('id')->on('qualifications');
            
            // Add expand_to_leads flag
            $table->boolean('expand_to_leads')->default(false)->after('qualification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            
            $table->dropForeign(['qualification_id']);
            $table->dropColumn('qualification_id');
            
            $table->dropColumn('expand_to_leads');
            
            // Re-add store_id
            $table->unsignedBigInteger('store_id')->after('description');
            $table->foreign('store_id')->references('id')->on('stores');
        });
    }
};