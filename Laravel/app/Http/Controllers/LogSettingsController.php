<?php

namespace App\Http\Controllers;

use App\Models\LogSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use App\Services\SystemLogService;

class LogSettingsController extends Controller
{
    /**
     * Get all settings grouped by category
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $grouped = LogSetting::getGrouped();
            
            return response()->json([
                'success' => true,
                'data' => $grouped,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single setting by key
     * 
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        try {
            $setting = LogSetting::where('key', $key)->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $setting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a single setting
     * 
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $setting = LogSetting::where('key', $key)->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $oldValue = $setting->value;
            $newValue = $request->input('value');

            // Convert boolean values
            if (is_bool($newValue)) {
                $newValue = $newValue ? 'true' : 'false';
            }

            $setting->value = (string) $newValue;
            $setting->save();

            // Clear cache
            LogSetting::clearCache();

            // Log the change
            SystemLogService::system()->info('Log setting updated', [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => $setting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update multiple settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updated = [];
            $failed = [];

            foreach ($request->input('settings') as $item) {
                $key = $item['key'];
                $value = $item['value'];

                $setting = LogSetting::where('key', $key)->first();
                
                if (!$setting) {
                    $failed[] = $key;
                    continue;
                }

                // Convert boolean values
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }

                $setting->value = (string) $value;
                $setting->save();
                $updated[] = $key;
            }

            // Clear cache
            LogSetting::clearCache();

            // Log the bulk update
            SystemLogService::system()->info('Log settings bulk updated', [
                'updated_keys' => $updated,
                'failed_keys' => $failed,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'updated' => $updated,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset all settings to default values
     * 
     * @return JsonResponse
     */
    public function resetToDefaults(): JsonResponse
    {
        try {
            // Drop and recreate the table with default values
            Artisan::call('migrate:refresh', [
                '--path' => 'database/migrations/2026_01_02_100000_create_log_settings_table.php',
                '--force' => true,
            ]);

            // Clear cache
            LogSetting::clearCache();

            // Log the reset
            SystemLogService::system()->warning('Log settings reset to defaults', [
                'reset_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings reset to defaults',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resetting settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run manual cleanup
     * 
     * @return JsonResponse
     */
    public function runCleanup(): JsonResponse
    {
        try {
            // Run the cleanup command
            Artisan::call('logs:cleanup');
            $output = Artisan::output();

            // Log the manual cleanup
            SystemLogService::system()->info('Manual log cleanup executed', [
                'executed_by' => auth()->id(),
                'output' => $output,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cleanup executed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error running cleanup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cleanup statistics
     * 
     * @return JsonResponse
     */
    public function getCleanupStats(): JsonResponse
    {
        try {
            $lastRun = LogSetting::get('cleanup_last_run');
            $enabled = LogSetting::isCleanupEnabled();
            $frequency = LogSetting::getCleanupFrequency();
            $time = LogSetting::getCleanupTime();

            // Calculate next run
            $nextRun = null;
            if ($enabled && $lastRun) {
                $lastRunDate = \Carbon\Carbon::parse($lastRun);
                switch ($frequency) {
                    case 'daily':
                        $nextRun = $lastRunDate->addDay()->format('Y-m-d') . ' ' . $time;
                        break;
                    case 'weekly':
                        $nextRun = $lastRunDate->addWeek()->format('Y-m-d') . ' ' . $time;
                        break;
                    case 'monthly':
                        $nextRun = $lastRunDate->addMonth()->format('Y-m-d') . ' ' . $time;
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => $enabled,
                    'frequency' => $frequency,
                    'time' => $time,
                    'last_run' => $lastRun,
                    'next_run' => $nextRun,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting cleanup stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
