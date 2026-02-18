<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add tracking for who performed the lead conversion.
     * This helps audit trail and accountability when SEU/BO converts a lead.
     */
    public function up(): void
    {
        Schema::table('lead_converteds', function (Blueprint $table) {
            $table->unsignedBigInteger('converted_by_user_id')->nullable()->after('cliente_id');
            
            $table->foreign('converted_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('lead_converteds', function (Blueprint $table) {
            $table->dropForeign(['converted_by_user_id']);
            $table->dropColumn('converted_by_user_id');
        });
    }
};