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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type', 50)->nullable()->after('notifica_html');
            $table->string('entity_type', 50)->nullable()->after('type');
            $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            
            // Index for faster queries
            $table->index(['to_user_id', 'visualizzato'], 'idx_notifications_user_read');
            $table->index(['entity_type', 'entity_id'], 'idx_notifications_entity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
            $table->dropIndex('idx_notifications_entity');
            $table->dropColumn(['type', 'entity_type', 'entity_id']);
        });
    }
};