<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use App\Models\contract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use App\Services\SystemLogService;

class LogController extends Controller
{
    /**
     * Display a paginated listing of logs with filters.
     * 
     * GET /api/logs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check permissions - only admin (role_id = 1) and backoffice (role_id = 2)
            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied. Only administrators and backoffice can view logs.'
                ], 403);
            }

            $query = Log::with('user:id,name,cognome,email');

            // Filter by source
            if ($request->filled('source') && $request->source !== 'all') {
                $query->source($request->source);
            }

            // Filter by level (can be comma-separated for multiple levels)
            if ($request->filled('level')) {
                $levels = explode(',', $request->level);
                $query->level($levels);
            }

            // Search in message
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by user
            if ($request->filled('user_id')) {
                $query->byUser($request->user_id);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->fromDate($request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->toDate($request->date_to);
            }

            // ==================== AUDIT TRAIL FILTERS ====================

            // Filter by entity type
            if ($request->filled('entity_type') && $request->entity_type !== 'all') {
                $query->forEntityType($request->entity_type);
            }

            // Filter by specific entity (requires both type and id)
            if ($request->filled('entity_id')) {
                $query->where('entity_id', $request->entity_id);
            }

            // Filter by contract ID (direct or via entity)
            if ($request->filled('contract_id')) {
                $query->forContract($request->contract_id);
            }

            // Filter by contract code (searches in context JSON and message)
            if ($request->filled('contract_code')) {
                $query->forContractCode($request->contract_code);
            }

            // Only logs with changes tracked (audit trail entries)
            if ($request->boolean('with_changes')) {
                $query->withChanges();
            }

            // ==================== DEVICE TRACKING FILTERS ====================

            if ($request->filled('device_fingerprint')) {
                $query->forFingerprint($request->device_fingerprint);
            }

            if ($request->filled('geo_country')) {
                $query->forCountry($request->geo_country);
            }

            if ($request->filled('geo_city')) {
                $query->forCity($request->geo_city);
            }

            if ($request->filled('geo_isp')) {
                $query->forIsp($request->geo_isp);
            }

            if ($request->filled('device_type')) {
                $query->forDeviceType($request->device_type);
            }

            if ($request->filled('device_os')) {
                $query->forOS($request->device_os);
            }

            if ($request->filled('device_browser')) {
                $query->forBrowser($request->device_browser);
            }

            if ($request->filled('screen_resolution')) {
                $query->forScreenResolution($request->screen_resolution);
            }

            if ($request->filled('timezone')) {
                $query->forTimezone($request->timezone);
            }

            // ==================== DATABASE OPERATION FILTERS ====================

            if ($request->filled('db_table') && $request->source === 'database') {
                $query->where('context->db_table', $request->db_table);
            }

            if ($request->filled('db_operation') && $request->source === 'database') {
                $query->where('context->db_operation', $request->db_operation);
            }

            // ==================== END FILTERS ====================

            // Sorting
            $sortBy = $request->input('sort_by', 'datetime');
            $sortDir = $request->input('sort_dir', 'desc');
            $allowedSortFields = ['id', 'datetime', 'level', 'source', 'entity_type', 'created_at'];
            
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDir);
            } else {
                $query->orderBy('datetime', 'desc');
            }

            // Pagination
            $perPage = min($request->input('per_page', 15), 100);
            $logs = $query->paginate($perPage);

            // Format response
            $formattedLogs = $logs->getCollection()->map(function ($log) {
                return $this->formatLog($log);
            });

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'data' => $formattedLogs,
                    'pagination' => [
                        'current_page' => $logs->currentPage(),
                        'last_page' => $logs->lastPage(),
                        'per_page' => $logs->perPage(),
                        'total' => $logs->total(),
                        'from' => $logs->firstItem(),
                        'to' => $logs->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::system()->exception($e, 'Error fetching logs');
            
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available filters for frontend dropdowns.
     * 
     * GET /api/logs/filters
     */
    public function getFilters(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            // Get entity types with counts
            $entityTypeCounts = Log::whereNotNull('entity_type')
                ->selectRaw('entity_type, COUNT(*) as count')
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray();

            $entityTypes = [];
            foreach (Log::getEntityTypes() as $key => $label) {
                $entityTypes[] = [
                    'key' => $key,
                    'label' => $label,
                    'count' => $entityTypeCounts[$key] ?? 0,
                ];
            }

            // Add "all" option
            array_unshift($entityTypes, [
                'key' => 'all',
                'label' => 'Tutti i tipi',
                'count' => array_sum($entityTypeCounts),
            ]);

            // Get sources with counts
            $sourceCounts = Log::selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();

            $sources = [];
            foreach (Log::getSources() as $key => $label) {
                $sources[] = [
                    'key' => $key,
                    'label' => $label,
                    'count' => $sourceCounts[$key] ?? 0,
                ];
            }

            array_unshift($sources, [
                'key' => 'all',
                'label' => 'Tutte le sorgenti',
                'count' => array_sum($sourceCounts),
            ]);

            // Get levels with counts
            $levelCounts = Log::selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray();

            $levels = [];
            foreach (Log::getLevels() as $key => $label) {
                $levels[] = [
                    'key' => $key,
                    'label' => $label,
                    'count' => $levelCounts[$key] ?? 0,
                ];
            }

            // Get users who have logs
            $usersWithLogs = User::select('id', 'name', 'cognome', 'email')
                ->whereIn('id', Log::distinct()->pluck('user_id')->filter())
                ->orderBy('name')
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => trim($u->name . ' ' . $u->cognome),
                    'email' => $u->email,
                ]);

            // ==================== DEVICE TRACKING FILTER OPTIONS ====================

            $countries = Log::whereNotNull('geo_country')
                ->selectRaw('geo_country, geo_country_code, COUNT(*) as count')
                ->groupBy('geo_country', 'geo_country_code')
                ->orderBy('count', 'desc')
                ->limit(50)
                ->get();

            $cities = Log::whereNotNull('geo_city')
                ->selectRaw('geo_city, COUNT(*) as count')
                ->groupBy('geo_city')
                ->orderBy('count', 'desc')
                ->limit(50)
                ->get();

            $isps = Log::whereNotNull('geo_isp')
                ->selectRaw('geo_isp, COUNT(*) as count')
                ->groupBy('geo_isp')
                ->orderBy('count', 'desc')
                ->limit(30)
                ->get();

            $deviceTypes = Log::whereNotNull('device_type')
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->orderBy('count', 'desc')
                ->get();

            $browsers = Log::whereNotNull('device_browser')
                ->selectRaw('device_browser, COUNT(*) as count')
                ->groupBy('device_browser')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get();

            $operatingSystems = Log::whereNotNull('device_os')
                ->selectRaw('device_os, COUNT(*) as count')
                ->groupBy('device_os')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get();

            $screenResolutions = Log::whereNotNull('screen_resolution')
                ->selectRaw('screen_resolution, COUNT(*) as count')
                ->groupBy('screen_resolution')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get();

            $timezones = Log::whereNotNull('timezone_client')
                ->selectRaw('timezone_client, COUNT(*) as count')
                ->groupBy('timezone_client')
                ->orderBy('count', 'desc')
                ->limit(30)
                ->get();


            // ==================== DATABASE OPERATION FILTER OPTIONS ====================

            $dbTables = [];
            $dbOperations = [];

            if ($request->input('source') === 'database') {
                $dbTables = Log::where('source', 'database')
                    ->whereNotNull('context')
                    ->whereRaw("JSON_EXTRACT(context, '$.db_table') IS NOT NULL")
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(context, '$.db_table')) as db_table, COUNT(*) as count")
                    ->groupBy('db_table')
                    ->orderBy('count', 'desc')
                    ->limit(50)
                    ->get();

                $dbOperations = Log::where('source', 'database')
                    ->whereNotNull('context')
                    ->whereRaw("JSON_EXTRACT(context, '$.db_operation') IS NOT NULL")
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(context, '$.db_operation')) as db_operation, COUNT(*) as count")
                    ->groupBy('db_operation')
                    ->orderBy('count', 'desc')
                    ->get();
            }

            // ==================== END DATABASE OPERATION FILTER OPTIONS ====================

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'entity_types' => $entityTypes,
                    'sources' => $sources,
                    'levels' => $levels,
                    'users' => $usersWithLogs,
                    // Device tracking options
                    'countries' => $countries,
                    'cities' => $cities,
                    'isps' => $isps,
                    'device_types' => $deviceTypes,
                    'browsers' => $browsers,
                    'operating_systems' => $operatingSystems,
                    'screen_resolutions' => $screenResolutions,
                    'timezones' => $timezones,
                    // Database operation options (only populated for database source)
                    'db_tables' => $dbTables,
                    'db_operations' => $dbOperations,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching filters: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete history for a specific contract (audit trail).
     * 
     * GET /api/logs/contract/{id}
     */
    public function getContractHistory(int $contractId): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            // Verify contract exists
            $contract = contract::find($contractId);
            
            if (!$contract) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Contract not found.'
                ], 404);
            }

            $limit = min(request()->input('limit', 100), 500);

            // Get all logs related to this contract
            $logs = Log::forContract($contractId)
                ->with('user:id,name,cognome,email')
                ->recent()
                ->limit($limit)
                ->get();

            // Format logs
            $formattedLogs = $logs->map(function ($log) {
                return $this->formatLog($log, true);
            });

            // Group by date for timeline view
            $groupedByDate = $formattedLogs->groupBy(function ($log) {
                return Carbon::parse($log['datetime'])->format('Y-m-d');
            });

            // Get summary stats
            $stats = [
                'total_logs' => $logs->count(),
                'by_entity_type' => $logs->groupBy('entity_type')->map->count(),
                'by_level' => $logs->groupBy('level')->map->count(),
                'logs_with_changes' => $logs->filter(fn($l) => $l->hasTrackedChanges())->count(),
                'date_range' => [
                    'first_log' => $logs->last()?->datetime?->format('Y-m-d H:i:s'),
                    'last_log' => $logs->first()?->datetime?->format('Y-m-d H:i:s'),
                ],
            ];

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'contract' => [
                        'id' => $contract->id,
                        'codice_contratto' => $contract->codice_contratto,
                    ],
                    'logs' => $formattedLogs,
                    'grouped_by_date' => $groupedByDate,
                    'stats' => $stats,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching contract history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get log statistics for dashboard.
     * 
     * GET /api/logs/stats
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            $source = $request->input('source');
            
            if ($source === 'all') {
                $source = null;
            }

            // Get counts by level
            $levelCounts = Log::query()
                ->when($source, fn($q) => $q->where('source', $source))
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray();

            // Get counts by source
            $sourceCounts = Log::selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();

            // Get counts by entity type
            $entityTypeCounts = Log::whereNotNull('entity_type')
                ->selectRaw('entity_type, COUNT(*) as count')
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray();

            // Get total count
            $totalCount = Log::query()
                ->when($source, fn($q) => $q->where('source', $source))
                ->count();

            // Errors today
            $errorsToday = Log::errors()
                ->today()
                ->when($source, fn($q) => $q->where('source', $source))
                ->count();

            // Logs today
            $logsToday = Log::today()
                ->when($source, fn($q) => $q->where('source', $source))
                ->count();

            // Logs last 7 days
            $logsLastWeek = Log::lastDays(7)
                ->when($source, fn($q) => $q->where('source', $source))
                ->count();

            // Audit trail stats
            $auditLogsToday = Log::withEntityTracking()
                ->today()
                ->when($source, fn($q) => $q->where('source', $source))
                ->count();

            $logsWithChangesToday = Log::withChanges()
                ->today()
                ->when($source, fn($q) => $q->where('source', $source))
                ->count();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'total' => $totalCount,
                    'by_level' => [
                        'debug' => $levelCounts[Log::LEVEL_DEBUG] ?? 0,
                        'info' => $levelCounts[Log::LEVEL_INFO] ?? 0,
                        'warning' => $levelCounts[Log::LEVEL_WARNING] ?? 0,
                        'error' => $levelCounts[Log::LEVEL_ERROR] ?? 0,
                        'critical' => $levelCounts[Log::LEVEL_CRITICAL] ?? 0,
                    ],
                    'by_source' => $sourceCounts,
                    'by_entity_type' => $entityTypeCounts,
                    'errors_today' => $errorsToday,
                    'logs_today' => $logsToday,
                    'logs_last_week' => $logsLastWeek,
                    'audit_logs_today' => $auditLogsToday,
                    'logs_with_changes_today' => $logsWithChangesToday,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get log volume for chart (last 24 hours, grouped by hour).
     * 
     * GET /api/logs/volume
     */
    public function getVolume(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            $source = $request->input('source');
            $hours = min($request->input('hours', 24), 168);

            if ($source === 'all') {
                $source = null;
            }

            $startTime = Carbon::now()->subHours($hours)->startOfHour();

            $query = Log::query()
                ->where('datetime', '>=', $startTime)
                ->when($source, fn($q) => $q->where('source', $source));

            $volumeData = $query
                ->selectRaw('DATE_FORMAT(datetime, "%Y-%m-%d %H:00") as hour')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(CASE WHEN level IN ("error", "critical") THEN 1 ELSE 0 END) as errors')
                ->selectRaw('SUM(CASE WHEN level = "warning" THEN 1 ELSE 0 END) as warnings')
                ->groupBy('hour')
                ->orderBy('hour', 'asc')
                ->get()
                ->keyBy('hour');

            $volume = [];
            $current = $startTime->copy();
            $now = Carbon::now();

            while ($current <= $now) {
                $hourKey = $current->format('Y-m-d H:00');
                $hourData = $volumeData->get($hourKey);

                $volume[] = [
                    'hour' => $hourKey,
                    'hour_formatted' => $current->format('H:i'),
                    'date_formatted' => $current->format('d/m'),
                    'count' => $hourData ? (int) $hourData->count : 0,
                    'errors' => $hourData ? (int) $hourData->errors : 0,
                    'warnings' => $hourData ? (int) $hourData->warnings : 0,
                ];

                $current->addHour();
            }

            $totalLogs = array_sum(array_column($volume, 'count'));
            $totalErrors = array_sum(array_column($volume, 'errors'));
            $totalWarnings = array_sum(array_column($volume, 'warnings'));
            $peakHour = !empty($volume) ? max(array_column($volume, 'count')) : 0;
            $avgPerHour = count($volume) > 0 ? round($totalLogs / count($volume), 1) : 0;

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'volume' => $volume,
                    'summary' => [
                        'total_logs' => $totalLogs,
                        'total_errors' => $totalErrors,
                        'total_warnings' => $totalWarnings,
                        'peak_hour' => $peakHour,
                        'avg_per_hour' => $avgPerHour,
                        'hours_covered' => count($volume),
                    ],
                    'filters' => [
                        'source' => $source ?? 'all',
                        'hours' => $hours,
                        'from' => $startTime->format('Y-m-d H:i:s'),
                        'to' => $now->format('Y-m-d H:i:s'),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching volume data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available log sources with counts.
     * 
     * GET /api/logs/sources
     */
    public function getSources(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            $sourceCounts = Log::selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();

            $sources = [];
            foreach (Log::getSources() as $key => $label) {
                $sources[] = [
                    'key' => $key,
                    'label' => $label,
                    'count' => $sourceCounts[$key] ?? 0,
                ];
            }

            array_unshift($sources, [
                'key' => 'all',
                'label' => 'Log di Sistema',
                'count' => array_sum($sourceCounts),
            ]);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['sources' => $sources]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching sources: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a specific log entry.
     * 
     * GET /api/logs/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            $log = Log::with('user:id,name,cognome,email')->findOrFail($id);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['log' => $this->formatLog($log, true)]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'response' => 'error',
                'status' => '404',
                'message' => 'Log not found.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a specific log entry.
     * 
     * DELETE /api/logs/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->role_id !== 1) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied. Only administrators can delete logs.'
                ], 403);
            }

            $log = Log::findOrFail($id);
            $log->delete();

            SystemLogService::system()->info('Log entry deleted', [
                'deleted_log_id' => $id,
                'deleted_by' => $user->id,
            ]);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'message' => 'Log deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'response' => 'error',
                'status' => '404',
                'message' => 'Log not found.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error deleting log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear logs by source or all logs.
     * 
     * DELETE /api/logs/clear
     */
    public function clearLogs(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->role_id !== 1) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied. Only administrators can clear logs.'
                ], 403);
            }

            $source = $request->input('source');
            
            if ($source && $source !== 'all') {
                $deletedCount = Log::where('source', $source)->delete();
                $message = "Cleared {$deletedCount} logs from source: {$source}";
            } else {
                $deletedCount = Log::truncate();
                $deletedCount = 'all';
                $message = "All logs have been cleared";
            }

            SystemLogService::system()->warning('Logs cleared', [
                'source' => $source ?? 'all',
                'deleted_count' => $deletedCount,
                'cleared_by' => $user->id,
            ]);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'message' => $message,
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error clearing logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export logs in various formats.
     * 
     * GET /api/logs/export
     */
    public function export(Request $request)
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            $format = $request->input('format', 'csv');
            
            $query = Log::with('user:id,name,cognome,email');

            if ($request->filled('source') && $request->source !== 'all') {
                $query->source($request->source);
            }

            if ($request->filled('level')) {
                $levels = explode(',', $request->level);
                $query->level($levels);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('date_from')) {
                $query->fromDate($request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->toDate($request->date_to);
            }

            if ($request->filled('entity_type') && $request->entity_type !== 'all') {
                $query->forEntityType($request->entity_type);
            }

            if ($request->filled('contract_id')) {
                $query->forContract($request->contract_id);
            }

            // Database operation filters (only for database source)
            if ($request->filled('db_table') && $request->source === 'database') {
                $query->where('context->db_table', $request->db_table);
            }

            if ($request->filled('db_operation') && $request->source === 'database') {
                $query->where('context->db_operation', $request->db_operation);
            }

            $logs = $query->orderBy('datetime', 'desc')->get();

            $source = $request->input('source', 'all');
            $filename = $source === 'all' ? 'laravel' : $source;
            
            if ($request->filled('entity_type') && $request->entity_type !== 'all') {
                $filename .= '_' . $request->entity_type;
            }
            
            if ($request->filled('contract_id')) {
                $filename .= '_contract_' . $request->contract_id;
            }

            if ($request->filled('db_table')) {
                $filename .= '_' . $request->db_table;
            }
            
            $timestamp = Carbon::now()->format('Y-m-d_His');

            SystemLogService::system()->info('Logs exported', [
                'format' => $format,
                'source' => $source,
                'entity_type' => $request->entity_type,
                'contract_id' => $request->contract_id,
                'count' => $logs->count(),
                'exported_by' => $user->id,
            ]);

            switch ($format) {
                case 'txt':
                    return $this->exportAsTxt($logs, "{$filename}_{$timestamp}.txt");
                
                case 'csv':
                    return $this->exportAsCsv($logs, "{$filename}_{$timestamp}.csv");
                
                case 'json':
                    return $this->exportAsJson($logs, "{$filename}_{$timestamp}.json");
                
                default:
                    return response()->json([
                        'response' => 'error',
                        'status' => '400',
                        'message' => 'Invalid format. Supported formats: txt, csv, json'
                    ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error exporting logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get log file content (raw file view).
     * 
     * GET /api/logs/file
     */
    public function getFileContent(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            $source = $request->input('source', 'all');
            $fromDb = $request->boolean('from_db', true);
            $limit = min($request->input('limit', 500), 1000);

            $filename = $this->getLogFilename($source);

            if ($fromDb) {
                return $this->getFileContentFromDb($request, $source, $filename, $limit);
            } else {
                return $this->getFileContentFromFile($source, $filename, $limit);
            }

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching file content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file content from database.
     */
    private function getFileContentFromDb(Request $request, string $source, string $filename, int $limit): JsonResponse
    {
        $query = Log::query();

        if ($source !== 'all') {
            $query->source($source);
        }

        if ($request->filled('level')) {
            $levels = explode(',', $request->level);
            $query->level($levels);
        }

        $logs = $query->orderBy('datetime', 'desc')->limit($limit)->get();

        $lines = $logs->map(function ($log, $index) {
            $line = "[{$log->datetime}] " . 
                    strtoupper($log->source) . "." . 
                    strtoupper($log->level) . ": " . 
                    $log->message;
            
            if ($log->stack_trace) {
                $line .= "\n" . $log->stack_trace;
            }

            return [
                'id' => $log->id,
                'line_number' => $index + 1,
                'content' => $line,
                'level' => $log->level,
                'source' => $log->source,
                'entity_type' => $log->entity_type,
                'has_stack_trace' => !empty($log->stack_trace),
            ];
        });

        return response()->json([
            'response' => 'ok',
            'status' => '200',
            'body' => [
                'filename' => $filename,
                'source' => 'database',
                'lines' => $lines,
                'total_lines' => $logs->count(),
            ]
        ]);
    }

    /**
     * Get file content from actual log file.
     */
    private function getFileContentFromFile(string $source, string $filename, int $limit): JsonResponse
    {
        $filePath = $this->getLogFilePath($source);

        if (!file_exists($filePath)) {
            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'filename' => $filename,
                    'source' => 'file',
                    'lines' => [],
                    'total_lines' => 0,
                    'message' => 'Log file does not exist yet.'
                ]
            ]);
        }

        $lines = $this->readLastLines($filePath, $limit);

        $formattedLines = [];
        $lineNumber = 1;

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $parsed = $this->parseLogLine($line);
            
            $formattedLines[] = [
                'id' => null,
                'line_number' => $lineNumber++,
                'content' => $line,
                'level' => $parsed['level'],
                'source' => $parsed['source'] ?? $source,
                'has_stack_trace' => $parsed['has_stack_trace'],
            ];
        }

        return response()->json([
            'response' => 'ok',
            'status' => '200',
            'body' => [
                'filename' => $filename,
                'source' => 'file',
                'file_path' => $filePath,
                'file_size' => $this->formatFileSize(filesize($filePath)),
                'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                'lines' => array_reverse($formattedLines),
                'total_lines' => count($formattedLines),
            ]
        ]);
    }

    /**
     * Get the log filename based on source.
     */
    private function getLogFilename(string $source): string
    {
        $fileMap = [
            'all' => 'laravel.log',
            'auth' => 'auth.log',
            // 'api' => 'api.log',
            'database' => 'database.log',
            'scheduler' => 'scheduler.log',
            'email' => 'email.log',
            'system' => 'system.log',
            'user_activity' => 'user_activity.log',
            'external_api' => 'external_api.log',
            'errors' => 'errors.log',
        ];

        return $fileMap[$source] ?? 'laravel.log';
    }

    /**
     * Get the full file path for a log source.
     */
    private function getLogFilePath(string $source): string
    {
        $filename = $this->getLogFilename($source);
        return storage_path('logs/' . $filename);
    }

    /**
     * Read the last N lines from a file efficiently.
     */
    private function readLastLines(string $filePath, int $lines): array
    {
        $result = [];
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = $file->fgets();
            if ($line !== false) {
                $result[] = rtrim($line);
            }
        }

        return $result;
    }

    /**
     * Parse a log line to extract level and other info.
     */
    private function parseLogLine(string $line): array
    {
        $level = 'info';
        $source = null;
        $hasStackTrace = false;

        if (preg_match('/\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]\s+\w+\.(\w+):/i', $line, $matches)) {
            $level = strtolower($matches[1]);
        }

        if (preg_match('/\[(\w+)\].*?\.(\w+):/i', $line, $matches)) {
            $source = strtolower($matches[1]);
            $level = strtolower($matches[2]);
        }

        if (strpos($line, '#0 ') !== false || strpos($line, 'Stack trace:') !== false) {
            $hasStackTrace = true;
        }

        $validLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        if (!in_array($level, $validLevels)) {
            $level = 'info';
        }

        if ($level === 'notice') $level = 'info';
        if (in_array($level, ['alert', 'emergency'])) $level = 'critical';

        return [
            'level' => $level,
            'source' => $source,
            'has_stack_trace' => $hasStackTrace,
        ];
    }

    /**
     * Format file size for display.
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get list of available log files.
     * 
     * GET /api/logs/files
     */
    public function getLogFiles(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 2])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied.'
                ], 403);
            }

            $logsPath = storage_path('logs/');
            $files = [];

            if (is_dir($logsPath)) {
                $logFiles = glob($logsPath . '*.log');
                
                foreach ($logFiles as $filePath) {
                    $filename = basename($filePath);
                    $files[] = [
                        'filename' => $filename,
                        'path' => $filePath,
                        'size' => $this->formatFileSize(filesize($filePath)),
                        'size_bytes' => filesize($filePath),
                        'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'source' => $this->guessSourceFromFilename($filename),
                    ];
                }

                usort($files, function ($a, $b) {
                    return filemtime($b['path']) - filemtime($a['path']);
                });
            }

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'files' => $files,
                    'total_count' => count($files),
                    'logs_directory' => $logsPath,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fetching log files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guess the source from filename.
     */
    private function guessSourceFromFilename(string $filename): string
    {
        $sourceMap = [
            'auth' => 'auth',
            // 'api' => 'api',
            'database' => 'database',
            'scheduler' => 'scheduler',
            'email' => 'email',
            'system' => 'system',
            'user_activity' => 'user_activity',
            'external_api' => 'external_api',
            'errors' => 'errors',
            'laravel' => 'all',
        ];

        foreach ($sourceMap as $key => $source) {
            if (strpos($filename, $key) === 0) {
                return $source;
            }
        }

        return 'all';
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Format a log entry for API response.
     */
    private function formatLog(Log $log, bool $full = false): array
    {
        $data = [
            'id' => $log->id,
            'level' => $log->level,
            'level_label' => $log->level_label,
            'source' => $log->source,
            'source_label' => $log->source_label,
            'message' => $full ? $log->message : $log->short_message,
            'datetime' => $log->datetime?->format('Y-m-d H:i:s'),
            'formatted_datetime' => $log->formatted_datetime,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => trim($log->user->name . ' ' . ($log->user->cognome ?? '')),
                'email' => $log->user->email,
            ] : null,
            'has_stack_trace' => $log->hasStackTrace(),
            // Audit trail fields
            'entity_type' => $log->entity_type,
            'entity_type_label' => $log->entity_type_label,
            'entity_id' => $log->entity_id,
            'contract_id' => $log->contract_id,
            'contract_code' => $log->context['contract_code'] ?? null,
            'has_tracked_changes' => $log->hasTrackedChanges(),
            // Device tracking (summary)
            'device_fingerprint' => $log->device_fingerprint,
            'device_type' => $log->device_type,
            'geo_city' => $log->geo_city,
            'geo_country' => $log->geo_country,
            'db_table' => $log->context['db_table'] ?? null,
            'db_operation' => $log->context['db_operation'] ?? null,
        ];

        if ($full) {
            $data['message'] = $log->message;
            $data['context'] = $log->context;
            $data['ip_address'] = $log->ip_address;
            $data['user_agent'] = $log->user_agent;
            $data['request_url'] = $log->request_url;
            $data['request_method'] = $log->request_method;
            $data['stack_trace'] = $log->stack_trace;
            $data['created_at'] = $log->created_at?->format('Y-m-d H:i:s');
            $data['updated_at'] = $log->updated_at?->format('Y-m-d H:i:s');
            
            // Include changes if present
            if ($log->hasTrackedChanges()) {
                $data['changes'] = $log->getTrackedChanges();
            }

            // Device info (full)
            $data['device_info'] = [
                'fingerprint' => $log->device_fingerprint,
                'type' => $log->device_type,
                'os' => $log->device_os,
                'browser' => $log->device_browser,
                'screen_resolution' => $log->screen_resolution,
                'cpu_cores' => $log->cpu_cores,
                'ram_gb' => $log->ram_gb,
                'timezone' => $log->timezone_client,
                'language' => $log->language,
                'touch_support' => $log->touch_support,
            ];

            // Geo info (full)
            $data['geo_info'] = [
                'country' => $log->geo_country,
                'country_code' => $log->geo_country_code,
                'region' => $log->geo_region,
                'city' => $log->geo_city,
                'isp' => $log->geo_isp,
                'timezone' => $log->geo_timezone,
            ];
        }

        return $data;
    }

    /**
     * Export logs as TXT file.
     */
    private function exportAsTxt($logs, string $filename)
    {
        $content = $logs->map(function ($log) {
            $line = "[{$log->datetime}] " . 
                    strtoupper($log->source) . "." . 
                    strtoupper($log->level) . ": " . 
                    $log->message;
            
            if ($log->entity_type) {
                $line .= " [Entity: {$log->entity_type}#{$log->entity_id}]";
            }
            
            if ($log->contract_id) {
                $line .= " [Contract: {$log->contract_id}]";
            }
            
            if ($log->user) {
                $line .= " (User: " . trim($log->user->name . ' ' . ($log->user->cognome ?? '')) . ")";
            }

            if ($log->stack_trace) {
                $line .= "\n" . $log->stack_trace;
            }

            return $line;
        })->implode("\n\n");

        return Response::make($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export logs as CSV file.
     */
    private function exportAsCsv($logs, string $filename)
    {
        $headers = ['ID', 'Datetime', 'Level', 'Source', 'Entity Type', 'Entity ID', 'Contract ID', 'Message', 'User', 'IP Address', 'City', 'Country', 'Device'];
        
        $rows = $logs->map(function ($log) {
            return [
                $log->id,
                $log->datetime?->format('Y-m-d H:i:s'),
                $log->level,
                $log->source,
                $log->entity_type ?? '-',
                $log->entity_id ?? '-',
                $log->contract_id ?? '-',
                str_replace(["\r", "\n"], ' ', $log->message),
                $log->user ? trim($log->user->name . ' ' . ($log->user->cognome ?? '')) : 'System',
                $log->ip_address ?? '-',
                $log->geo_city ?? '-',
                $log->geo_country ?? '-',
                $log->device_type ?? '-',
            ];
        });

        $content = implode(',', $headers) . "\n";
        
        foreach ($rows as $row) {
            $escapedRow = array_map(function ($field) {
                $field = str_replace('"', '""', $field ?? '');
                if (strpos($field, ',') !== false || strpos($field, '"') !== false) {
                    return '"' . $field . '"';
                }
                return $field;
            }, $row);
            
            $content .= implode(',', $escapedRow) . "\n";
        }

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export logs as JSON file.
     */
    private function exportAsJson($logs, string $filename)
    {
        $data = $logs->map(function ($log) {
            return $this->formatLog($log, true);
        });

        $content = json_encode([
            'exported_at' => Carbon::now()->toIso8601String(),
            'total_count' => $logs->count(),
            'logs' => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return Response::make($content, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}