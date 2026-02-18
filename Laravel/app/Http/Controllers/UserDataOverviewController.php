<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SystemLogService;

class UserDataOverviewController extends Controller
{
    /**
     * Get API key from environment variable.
     */
    private function getApiKey(): string
    {
        return env('USER_DATA_API_KEY', '');
    }

    /**
     * Verify API key from header or query param.
     */
    private function verifyApiKey(Request $request): bool
    {
        $providedKey = $request->header('X-API-Key') ?? $request->input('api_key');
        $expectedKey = $this->getApiKey();
        
        if (empty($expectedKey)) {
            SystemLogService::externalApi()->critical('USER_DATA_API_KEY not configured in .env');
            return false;
        }
        
        return $providedKey === $expectedKey;
    }

    /**
     * Get client identifier for logging (IP address since no user auth).
     */
    private function getClientIdentifier(Request $request): string
    {
        return $request->ip() ?? 'unknown';
    }

    /**
     * Get all user data overview records.
     * All filters are combinable with each other.
     * 
     * GET /api/user-data-overview
     * GET /api/user-data-overview?user_ids=1,2,3
     * GET /api/user-data-overview?role_id=2
     * GET /api/user-data-overview?ruolo_lead=Cliente
     * GET /api/user-data-overview?role_id=3&ruolo_lead=Cliente
     * GET /api/user-data-overview?user_id_padre=16
     * GET /api/user-data-overview?padre_role_id=3
     */
    public function index(Request $request)
    {
        $clientIp = $this->getClientIdentifier($request);

        if (!$this->verifyApiKey($request)) {
            SystemLogService::externalApi()->warning('Unauthorized access attempt to user-data-overview', [
                'client_ip' => $clientIp,
                'endpoint' => 'index',
            ]);

            return response()->json([
                "response" => "ko",
                "status" => "401",
                "body" => ["error" => "Unauthorized - Invalid API Key"]
            ], 401);
        }

        try {
            $query = DB::table('user_data_overview')
                ->select([
                    'id',
                    'user_id',
                    'codice',
                    'name',
                    'cognome',
                    'ragione_sociale',
                    'role_id',
                    'role_name',
                    'email',
                    'user_id_padre',
                    'nome_user_padre',
                    'stato_user',
                    'punti_valore_maturati',
                    'punti_carriera_maturati',
                    'punti_bonus',
                    'punti_spesi',
                    'lead_id',
                    'nome_lead',
                    'ruolo_lead',
                    'last_sync'
                ])
                ->orderBy('user_id')
                ->orderBy('id');

            // Filter by user IDs if provided
            if ($request->has('user_ids')) {
                $userIds = explode(',', $request->input('user_ids'));
                $userIds = array_map('trim', $userIds);
                $userIds = array_filter($userIds, 'is_numeric');
                
                if (!empty($userIds)) {
                    $query->whereIn('user_id', $userIds);
                }
            }

            // Filter by role_id if provided
            if ($request->has('role_id')) {
                $roleId = (int)$request->input('role_id');
                $query->where('role_id', $roleId);
            }

            // Filter by user_id_padre if provided
            if ($request->has('user_id_padre')) {
                $userIdPadre = (int)$request->input('user_id_padre');
                $query->where('user_id_padre', $userIdPadre);
            }

            // Filter by parent's role_id (e.g. all users whose padre is a Cliente)
            if ($request->has('padre_role_id')) {
                $padreRoleId = (int)$request->input('padre_role_id');
                $query->whereIn('user_id_padre', function ($sub) use ($padreRoleId) {
                    $sub->select('id')
                        ->from('users')
                        ->where('role_id', $padreRoleId);
                });
            }

            // Filter by ruolo_lead (combinable with all other filters)
            if ($request->has('ruolo_lead')) {
                $ruoloLead = $request->input('ruolo_lead');
                $query->where('ruolo_lead', $ruoloLead);
            }

            // Filter to show only users that have a lead (invitato_da)
            if ($request->has('has_lead') && $request->input('has_lead') == '1') {
                $query->whereNotNull('lead_id');
            }

            // Limit results only if explicitly requested
            if ($request->has('limit')) {
                $limit = (int)$request->input('limit');
                $query->limit($limit);
            }

            $data = $query->get();

            // Log the access
            SystemLogService::externalApi()->info('User data overview accessed via Google Sheets', [
                'client_ip' => $clientIp,
                'endpoint' => 'index',
                'filters' => array_filter([
                    'user_ids' => $request->input('user_ids'),
                    'role_id' => $request->input('role_id'),
                    'user_id_padre' => $request->input('user_id_padre'),
                    'padre_role_id' => $request->input('padre_role_id'),
                    'ruolo_lead' => $request->input('ruolo_lead'),
                    'has_lead' => $request->input('has_lead'),
                ]),
                'records_returned' => $data->count(),
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "count" => $data->count(),
                    "data" => $data
                ]
            ]);

        } catch (\Exception $e) {
            SystemLogService::externalApi()->error('Error in user-data-overview index', [
                'client_ip' => $clientIp,
                'error' => $e->getMessage(),
            ], $e);

            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Bulk update multiple records.
     * Expects JSON body with "updates" array.
     * 
     * Editable fields: codice, name, cognome, ragione_sociale, email, stato_user,
     *                   punti_valore_maturati, punti_carriera_maturati, punti_bonus, punti_spesi
     * Read-only fields: role_name, nome_user_padre, lead_id, nome_lead, ruolo_lead
     * 
     * The trigger will propagate changes to the users table automatically.
     * 
     * POST /api/user-data-overview/bulk-update
     */
    public function bulkUpdate(Request $request)
    {
        $clientIp = $this->getClientIdentifier($request);

        if (!$this->verifyApiKey($request)) {
            SystemLogService::externalApi()->warning('Unauthorized bulk update attempt to user-data-overview', [
                'client_ip' => $clientIp,
                'endpoint' => 'bulkUpdate',
            ]);

            return response()->json([
                "response" => "ko",
                "status" => "401",
                "body" => ["error" => "Unauthorized - Invalid API Key"]
            ], 401);
        }

        try {
            $updates = $request->input('updates', []);
            
            if (empty($updates)) {
                return response()->json([
                    "response" => "ko",
                    "status" => "400",
                    "body" => ["error" => "No updates provided"]
                ], 400);
            }

            $updatedCount = 0;
            $errors = [];
            $updatedRecords = [];

            // Editable fields from Google Sheets
            $stringFields = ['codice', 'name', 'cognome', 'ragione_sociale', 'email'];
            $intFields = ['stato_user', 'punti_valore_maturati', 'punti_carriera_maturati', 'punti_bonus', 'punti_spesi'];

            DB::beginTransaction();

            foreach ($updates as $index => $update) {
                if (!isset($update['id'])) {
                    $errors[] = "Row $index: Missing id";
                    continue;
                }

                $id = (int)$update['id'];
                $updateData = [];
                $changes = [];
                
                // Get current record for comparison
                $currentRecord = DB::table('user_data_overview')
                    ->where('id', $id)
                    ->first();

                if (!$currentRecord) {
                    $errors[] = "Row $index: Record ID $id not found";
                    continue;
                }

                // Process string fields
                foreach ($stringFields as $field) {
                    if (array_key_exists($field, $update)) {
                        $val = $update[$field];
                        $newVal = ($val === '') ? null : $val;
                        $currentVal = $currentRecord->$field;

                        if ($currentVal !== $newVal) {
                            $updateData[$field] = $newVal;
                            $changes[$field] = [
                                'old' => $currentVal,
                                'new' => $newVal
                            ];
                        }
                    }
                }

                // Process integer fields
                foreach ($intFields as $field) {
                    if (array_key_exists($field, $update)) {
                        $val = $update[$field];
                        $newVal = ($val === '' || $val === null) ? 0 : (int)$val;
                        $currentVal = (int)$currentRecord->$field;

                        if ($currentVal !== $newVal) {
                            $updateData[$field] = $newVal;
                            $changes[$field] = [
                                'old' => $currentVal,
                                'new' => $newVal
                            ];
                        }
                    }
                }

                if (!empty($updateData)) {
                    $affected = DB::table('user_data_overview')
                        ->where('id', $id)
                        ->update($updateData);
                    
                    if ($affected > 0) {
                        $updatedCount++;
                        
                        // Log each individual record update
                        SystemLogService::externalApi()->info('User data updated via Google Sheets', [
                            'record_id' => $id,
                            'user_id' => $currentRecord->user_id,
                            'codice' => $currentRecord->codice,
                            'user_name' => trim(($currentRecord->name ?? '') . ' ' . ($currentRecord->cognome ?? '')),
                            'changes' => $changes,
                            'client_ip' => $clientIp,
                        ]);

                        $updatedRecords[] = [
                            'id' => $id,
                            'user_id' => $currentRecord->user_id,
                            'codice' => $currentRecord->codice,
                            'changes' => $changes,
                        ];
                    }
                }
            }

            DB::commit();

            // Log summary of bulk update
            if ($updatedCount > 0) {
                SystemLogService::externalApi()->info('User bulk update completed via Google Sheets', [
                    'client_ip' => $clientIp,
                    'total_requested' => count($updates),
                    'total_updated' => $updatedCount,
                    'errors_count' => count($errors),
                    'user_ids_affected' => array_unique(array_column($updatedRecords, 'user_id')),
                ]);
            }

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "updated_count" => $updatedCount,
                    "errors" => $errors,
                    "message" => "Aggiornati $updatedCount record. I trigger sincronizzeranno la tabella users automaticamente."
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            SystemLogService::externalApi()->error('Error in user-data-overview bulkUpdate', [
                'client_ip' => $clientIp,
                'updates_count' => count($updates ?? []),
                'error' => $e->getMessage(),
            ], $e);

            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Get single record by ID.
     * 
     * GET /api/user-data-overview/{id}
     */
    public function show(Request $request, $id)
    {
        $clientIp = $this->getClientIdentifier($request);

        if (!$this->verifyApiKey($request)) {
            SystemLogService::externalApi()->warning('Unauthorized access attempt to show user record', [
                'client_ip' => $clientIp,
                'endpoint' => 'show',
                'record_id' => $id,
            ]);

            return response()->json([
                "response" => "ko",
                "status" => "401",
                "body" => ["error" => "Unauthorized - Invalid API Key"]
            ], 401);
        }

        try {
            $record = DB::table('user_data_overview')->where('id', $id)->first();

            if (!$record) {
                return response()->json([
                    "response" => "ko",
                    "status" => "404",
                    "body" => ["error" => "Record not found"]
                ], 404);
            }

            SystemLogService::externalApi()->debug('Single user record accessed via Google Sheets', [
                'client_ip' => $clientIp,
                'record_id' => $id,
                'user_id' => $record->user_id,
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["data" => $record]
            ]);

        } catch (\Exception $e) {
            SystemLogService::externalApi()->error('Error in user-data-overview show', [
                'client_ip' => $clientIp,
                'record_id' => $id,
                'error' => $e->getMessage(),
            ], $e);

            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }
}