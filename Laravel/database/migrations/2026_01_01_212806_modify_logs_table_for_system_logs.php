<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modifies the logs table for system logging:
     * - Adds new columns: level, source, context, ip_address, user_agent, request_url, request_method, stack_trace
     * - Renames 'tipo_di_operazione' to 'message' (MariaDB syntax)
     * - Makes 'user_id' nullable for system logs without user context
     * - Adds indexes for query performance
     * - Backfills existing records
     */
    public function up(): void
    {
        // Step 1: Add all new columns
        if (!Schema::hasColumn('logs', 'level')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `level` VARCHAR(20) NULL DEFAULT 'info' AFTER `id`");
        }

        if (!Schema::hasColumn('logs', 'source')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `source` VARCHAR(50) NULL DEFAULT 'system' AFTER `level`");
        }

        if (!Schema::hasColumn('logs', 'context')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `context` JSON NULL AFTER `datetime`");
        }

        if (!Schema::hasColumn('logs', 'ip_address')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `ip_address` VARCHAR(45) NULL AFTER `context`");
        }

        if (!Schema::hasColumn('logs', 'user_agent')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `user_agent` TEXT NULL AFTER `ip_address`");
        }

        if (!Schema::hasColumn('logs', 'request_url')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `request_url` TEXT NULL AFTER `user_agent`");
        }

        if (!Schema::hasColumn('logs', 'request_method')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `request_method` VARCHAR(10) NULL AFTER `request_url`");
        }

        if (!Schema::hasColumn('logs', 'stack_trace')) {
            DB::statement("ALTER TABLE `logs` ADD COLUMN `stack_trace` LONGTEXT NULL AFTER `request_method`");
        }

        // Step 2: Rename 'tipo_di_operazione' to 'message' using MariaDB compatible syntax
        if (Schema::hasColumn('logs', 'tipo_di_operazione') && !Schema::hasColumn('logs', 'message')) {
            DB::statement("ALTER TABLE `logs` CHANGE COLUMN `tipo_di_operazione` `message` TEXT NOT NULL");
        }

        // Step 3: Drop the foreign key constraint on user_id
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'logs' 
            AND CONSTRAINT_NAME = 'logs_user_id_foreign'
        ");

        if (!empty($foreignKeyExists)) {
            DB::statement("ALTER TABLE `logs` DROP FOREIGN KEY `logs_user_id_foreign`");
        }

        // Step 4: Make user_id nullable
        DB::statement("ALTER TABLE `logs` MODIFY COLUMN `user_id` BIGINT(20) UNSIGNED NULL");

        // Step 5: Re-add the foreign key with ON DELETE SET NULL
        DB::statement("
            ALTER TABLE `logs` 
            ADD CONSTRAINT `logs_user_id_foreign` 
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
            ON DELETE SET NULL
        ");

        // Step 6: Add indexes for better query performance
        $this->addIndexIfNotExists('logs', 'logs_level_index', 'level');
        $this->addIndexIfNotExists('logs', 'logs_source_index', 'source');
        $this->addIndexIfNotExists('logs', 'logs_datetime_index', 'datetime');
        $this->addCompositeIndexIfNotExists('logs', 'logs_source_level_index', ['source', 'level']);
        $this->addCompositeIndexIfNotExists('logs', 'logs_source_datetime_index', ['source', 'datetime']);

        // Step 7: Backfill existing records - set level to 'info' where NULL
        DB::table('logs')
            ->whereNull('level')
            ->update(['level' => 'info']);

        // Step 8: Backfill source to 'user_activity' where NULL
        DB::table('logs')
            ->whereNull('source')
            ->update(['source' => 'user_activity']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop indexes
        $this->dropIndexIfExists('logs', 'logs_level_index');
        $this->dropIndexIfExists('logs', 'logs_source_index');
        $this->dropIndexIfExists('logs', 'logs_datetime_index');
        $this->dropIndexIfExists('logs', 'logs_source_level_index');
        $this->dropIndexIfExists('logs', 'logs_source_datetime_index');

        // Step 2: Drop foreign key
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'logs' 
            AND CONSTRAINT_NAME = 'logs_user_id_foreign'
        ");

        if (!empty($foreignKeyExists)) {
            DB::statement("ALTER TABLE `logs` DROP FOREIGN KEY `logs_user_id_foreign`");
        }

        // Step 3: Set NULL user_ids to 1 (admin) before making NOT NULL
        DB::table('logs')
            ->whereNull('user_id')
            ->update(['user_id' => 1]);

        // Step 4: Make user_id NOT NULL again
        DB::statement("ALTER TABLE `logs` MODIFY COLUMN `user_id` BIGINT(20) UNSIGNED NOT NULL");

        // Step 5: Re-add original foreign key
        DB::statement("
            ALTER TABLE `logs` 
            ADD CONSTRAINT `logs_user_id_foreign` 
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ");

        // Step 6: Rename 'message' back to 'tipo_di_operazione'
        if (Schema::hasColumn('logs', 'message')) {
            DB::statement("ALTER TABLE `logs` CHANGE COLUMN `message` `tipo_di_operazione` TEXT NOT NULL");
        }

        // Step 7: Drop new columns
        $columnsToDrop = ['level', 'source', 'context', 'ip_address', 'user_agent', 'request_url', 'request_method', 'stack_trace'];
        
        foreach ($columnsToDrop as $column) {
            if (Schema::hasColumn('logs', $column)) {
                Schema::table('logs', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Add single column index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, string $indexName, string $column): void
    {
        $indexExists = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ", [$table, $indexName]);

        if (empty($indexExists)) {
            DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` (`{$column}`)");
        }
    }

    /**
     * Add composite index if it doesn't exist
     */
    private function addCompositeIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        $indexExists = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ", [$table, $indexName]);

        if (empty($indexExists)) {
            $columnList = implode('`, `', $columns);
            DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` (`{$columnList}`)");
        }
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexExists = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ", [$table, $indexName]);

        if (!empty($indexExists)) {
            DB::statement("DROP INDEX `{$indexName}` ON `{$table}`");
        }
    }
};