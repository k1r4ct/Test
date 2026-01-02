<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
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
     * 
     * Query params:
     * - source: string (auth, api, database, scheduler, email, system, user_activity)
     * - level: string or comma-separated (debug, info, warning, error, critical)
     * - search: string (search in message)
     * - user_id: int
     * - date_from: string (Y-m-d)
     * - date_to: string (Y-m-d)
     * - per_page: int (default 15)
     * - sort_by: string (default 'datetime')
     * - sort_dir: string (default 'desc')
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

            $query = Log::with('user:id,name,email');

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

            // Sorting
            $sortBy = $request->input('sort_by', 'datetime');
            $sortDir = $request->input('sort_dir', 'desc');
            $allowedSortFields = ['id', 'datetime', 'level', 'source', 'created_at'];
            
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
     * Get log statistics for dashboard.
     * 
     * GET /api/logs/stats
     * 
     * Query params:
     * - source: string (optional, filter stats by source)
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
                    'errors_today' => $errorsToday,
                    'logs_today' => $logsToday,
                    'logs_last_week' => $logsLastWeek,
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

            // Add "all" option
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

            $log = Log::with('user:id,name,email')->findOrFail($id);

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

            // Only admin can delete logs
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
     * 
     * Query params:
     * - source: string (optional, if not provided clears all logs)
     */
    public function clearLogs(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Only admin can clear logs
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
     * 
     * Query params:
     * - format: string (txt, csv, json) - pdf and xls require additional libraries
     * - source: string (optional)
     * - level: string (optional, comma-separated)
     * - date_from: string (optional)
     * - date_to: string (optional)
     * - search: string (optional)
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
            
            // Build query with filters
            $query = Log::with('user:id,name,email');

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

            $logs = $query->orderBy('datetime', 'desc')->get();

            // Generate filename
            $source = $request->input('source', 'all');
            $filename = $source === 'all' ? 'laravel' : $source;
            $timestamp = Carbon::now()->format('Y-m-d_His');

            // Log the export action
            SystemLogService::system()->info('Logs exported', [
                'format' => $format,
                'source' => $source,
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
     * 
     * Query params:
     * - source: string (optional) - which log file to read
     * - from_db: bool (default true) - if true reads from DB, if false reads from file
     * - level: string (optional, comma-separated) - filter by level (only for DB mode)
     * - limit: int (default 500) - max lines to return
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

            // Determine filename
            $filename = $this->getLogFilename($source);

            if ($fromDb) {
                // Read from database
                return $this->getFileContentFromDb($request, $source, $filename, $limit);
            } else {
                // Read from actual log file
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

        // Format as file content
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

        // Read file from the end (most recent entries)
        $lines = $this->readLastLines($filePath, $limit);

        // Parse and format lines
        $formattedLines = [];
        $lineNumber = 1;

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $parsed = $this->parseLogLine($line);
            
            $formattedLines[] = [
                'id' => null, // No DB id for file lines
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
                'lines' => array_reverse($formattedLines), // Most recent first
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
            'api' => 'api.log',
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
        $file->seek(PHP_INT_MAX); // Go to end
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

        // Try to match Laravel log format: [YYYY-MM-DD HH:MM:SS] environment.LEVEL: message
        if (preg_match('/\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]\s+\w+\.(\w+):/i', $line, $matches)) {
            $level = strtolower($matches[1]);
        }

        // Try to match our custom format: [SOURCE.LEVEL]
        if (preg_match('/\[(\w+)\].*?\.(\w+):/i', $line, $matches)) {
            $source = strtolower($matches[1]);
            $level = strtolower($matches[2]);
        }

        // Check for stack trace indicators
        if (strpos($line, '#0 ') !== false || strpos($line, 'Stack trace:') !== false) {
            $hasStackTrace = true;
        }

        // Normalize level
        $validLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        if (!in_array($level, $validLevels)) {
            $level = 'info';
        }

        // Map notice/alert/emergency to our levels
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

                // Sort by last modified (most recent first)
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
            'api' => 'api',
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
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'has_stack_trace' => $log->hasStackTrace(),
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
            
            if ($log->user) {
                $line .= " (User: {$log->user->name})";
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
        $headers = ['ID', 'Datetime', 'Level', 'Source', 'Message', 'User', 'IP Address'];
        
        $rows = $logs->map(function ($log) {
            return [
                $log->id,
                $log->datetime?->format('Y-m-d H:i:s'),
                $log->level,
                $log->source,
                str_replace(["\r", "\n"], ' ', $log->message), // Remove newlines for CSV
                $log->user?->name ?? 'System',
                $log->ip_address ?? '-',
            ];
        });

        $content = implode(',', $headers) . "\n";
        
        foreach ($rows as $row) {
            $escapedRow = array_map(function ($field) {
                // Escape quotes and wrap in quotes if contains comma
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