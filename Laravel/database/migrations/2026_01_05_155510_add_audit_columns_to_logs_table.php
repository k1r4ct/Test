<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds audit columns to logs table for efficient filtering:
     * - entity_type: Type of entity being logged (contract, user, customer_data, etc.)
     * - entity_id: ID of the specific entity
     * - contract_id: Direct reference to contract for quick filtering
     */
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            // Add entity tracking columns after 'source'
            $table->string('entity_type', 50)->nullable()->after('source');
            $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            $table->unsignedBigInteger('contract_id')->nullable()->after('entity_id');

            // Add indexes for fast queries
            $table->index('entity_type', 'logs_entity_type_index');
            $table->index('entity_id', 'logs_entity_id_index');
            $table->index('contract_id', 'logs_contract_id_index');
            
            // Composite index for common query pattern
            $table->index(['entity_type', 'entity_id'], 'logs_entity_composite_index');
            $table->index(['contract_id', 'datetime'], 'logs_contract_datetime_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('logs_entity_type_index');
            $table->dropIndex('logs_entity_id_index');
            $table->dropIndex('logs_contract_id_index');
            $table->dropIndex('logs_entity_composite_index');
            $table->dropIndex('logs_contract_datetime_index');

            // Drop columns
            $table->dropColumn(['entity_type', 'entity_id', 'contract_id']);
        });
    }
};