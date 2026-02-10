<?php

namespace App\Traits;

use App\Services\SystemLogService;

/**
 * Trait LogsDatabaseOperations
 *
 * Automatically logs database operations (INSERT, UPDATE, DELETE) to the
 * 'database' source when a model is created, updated, or deleted.
 *
 * This creates a SEPARATE, minimal log entry independent from any existing
 * rich logging in the model's booted() method. The two coexist:
 *   - Existing booted() logs → rich business context (userActivity/database/ecommerce)
 *   - This trait          → technical audit trail with db_table + db_operation
 *
 * Usage: add `use LogsDatabaseOperations;` in any Eloquent model.
 *
 * The trait stores db_table and db_operation in the JSON context field,
 * enabling frontend filtering without any database migration.
 */
trait LogsDatabaseOperations
{
    /**
     * Boot the trait — registers model lifecycle event listeners
     * for automatic database operation logging.
     *
     * Laravel automatically calls boot{TraitName}() for each trait,
     * so this runs alongside the model's own booted() without conflicts.
     */
    public static function bootLogsDatabaseOperations(): void
    {
        // ---- INSERT ----
        static::created(function ($model) {
            SystemLogService::logDbOperation(
                $model->getTable(),
                'INSERT',
                $model->getKey()
            );
        });

        // ---- UPDATE (skip if only timestamps changed) ----
        static::updated(function ($model) {
            $changedFields = array_keys(
                array_diff_key(
                    $model->getChanges(),
                    array_flip(['created_at', 'updated_at'])
                )
            );

            if (empty($changedFields)) {
                return;
            }

            SystemLogService::logDbOperation(
                $model->getTable(),
                'UPDATE',
                $model->getKey(),
                ['changed_fields' => $changedFields]
            );
        });

        // ---- DELETE ----
        static::deleted(function ($model) {
            SystemLogService::logDbOperation(
                $model->getTable(),
                'DELETE',
                $model->getKey(),
                [],
                'warning'
            );
        });
    }
}