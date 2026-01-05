<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SystemLogService;

class ContractDataOverviewController extends Controller
{
    /**
     * Get API key from environment variable.
     */
    private function getApiKey(): string
    {
        return env('CONTRACT_DATA_API_KEY', '');
    }

    /**
     * Verify API key from header or query param.
     */
    private function verifyApiKey(Request $request): bool
    {
        $providedKey = $request->header('X-API-Key') ?? $request->input('api_key');
        $expectedKey = $this->getApiKey();
        
        if (empty($expectedKey)) {
            SystemLogService::externalApi()->critical('CONTRACT_DATA_API_KEY not configured in .env');
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
     * Get all contract data overview records.
     * Optionally filter by contract_ids.
     */
    public function index(Request $request)
    {
        $clientIp = $this->getClientIdentifier($request);

        if (!$this->verifyApiKey($request)) {
            SystemLogService::externalApi()->warning('Unauthorized access attempt to contract-data-overview', [
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
            $query = DB::table('contract_data_overview')
                ->select([
                    'id',
                    'specific_data_id',
                    'contract_id',
                    'codice_contratto',
                    'domanda',
                    'risposta_tipo_bool',
                    'risposta_tipo_numero',
                    'risposta_tipo_stringa',
                    'last_sync'
                ])
                ->orderBy('contract_id')
                ->orderBy('id');

            // Filter by contract IDs if provided
            $contractIds = null;
            if ($request->has('contract_ids')) {
                $contractIds = explode(',', $request->input('contract_ids'));
                $contractIds = array_map('trim', $contractIds);
                $contractIds = array_filter($contractIds, 'is_numeric');
                
                if (!empty($contractIds)) {
                    $query->whereIn('contract_id', $contractIds);
                }
            }

            // Limit results only if explicitly requested
            if ($request->has('limit')) {
                $limit = (int)$request->input('limit');
                $query->limit($limit);
            }

            $data = $query->get();

            // Log the access
            SystemLogService::externalApi()->info('Contract data overview accessed via Google Sheets', [
                'client_ip' => $clientIp,
                'endpoint' => 'index',
                'contract_ids_filter' => $contractIds,
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
            SystemLogService::externalApi()->error('Error in contract-data-overview index', [
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
     * This is the main endpoint used by Google Sheets to update contract data.
     */
    public function bulkUpdate(Request $request)
    {
        $clientIp = $this->getClientIdentifier($request);

        if (!$this->verifyApiKey($request)) {
            SystemLogService::externalApi()->warning('Unauthorized bulk update attempt', [
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
            $updatedRecords = []; // Track what was updated for logging

            DB::beginTransaction();

            foreach ($updates as $index => $update) {
                if (!isset($update['id'])) {
                    $errors[] = "Row $index: Missing id";
                    continue;
                }

                $id = (int)$update['id'];
                $updateData = [];
                $changes = []; // Track changes for this record
                
                // Get current record for comparison
                $currentRecord = DB::table('contract_data_overview')
                    ->where('id', $id)
                    ->first();

                if (!$currentRecord) {
                    $errors[] = "Row $index: Record ID $id not found";
                    continue;
                }

                // Handle risposta_tipo_bool
                if (array_key_exists('risposta_tipo_bool', $update)) {
                    $val = $update['risposta_tipo_bool'];
                    $newVal = ($val === '' || $val === null) ? null : (int)$val;
                    if ($currentRecord->risposta_tipo_bool !== $newVal) {
                        $updateData['risposta_tipo_bool'] = $newVal;
                        $changes['risposta_tipo_bool'] = [
                            'old' => $currentRecord->risposta_tipo_bool,
                            'new' => $newVal
                        ];
                    }
                }
                
                // Handle risposta_tipo_numero
                if (array_key_exists('risposta_tipo_numero', $update)) {
                    $val = $update['risposta_tipo_numero'];
                    $newVal = ($val === '' || $val === null) ? null : (float)$val;
                    if ((float)$currentRecord->risposta_tipo_numero !== $newVal) {
                        $updateData['risposta_tipo_numero'] = $newVal;
                        $changes['risposta_tipo_numero'] = [
                            'old' => $currentRecord->risposta_tipo_numero,
                            'new' => $newVal
                        ];
                    }
                }
                
                // Handle risposta_tipo_stringa
                if (array_key_exists('risposta_tipo_stringa', $update)) {
                    $val = $update['risposta_tipo_stringa'];
                    $newVal = ($val === '') ? null : $val;
                    if ($currentRecord->risposta_tipo_stringa !== $newVal) {
                        $updateData['risposta_tipo_stringa'] = $newVal;
                        $changes['risposta_tipo_stringa'] = [
                            'old' => $currentRecord->risposta_tipo_stringa,
                            'new' => $newVal
                        ];
                    }
                }

                if (!empty($updateData)) {
                    $affected = DB::table('contract_data_overview')
                        ->where('id', $id)
                        ->update($updateData);
                    
                    if ($affected > 0) {
                        $updatedCount++;
                        
                        // Log each individual record update
                        SystemLogService::externalApi()->info('Contract data updated via Google Sheets', [
                            'record_id' => $id,
                            'contract_id' => $currentRecord->contract_id,
                            'codice_contratto' => $currentRecord->codice_contratto,
                            'domanda' => $currentRecord->domanda,
                            'changes' => $changes,
                            'client_ip' => $clientIp,
                        ]);

                        $updatedRecords[] = [
                            'id' => $id,
                            'contract_id' => $currentRecord->contract_id,
                            'codice_contratto' => $currentRecord->codice_contratto,
                            'changes' => $changes,
                        ];
                    }
                }
            }

            DB::commit();

            // Log summary of bulk update
            if ($updatedCount > 0) {
                SystemLogService::externalApi()->info('Bulk update completed via Google Sheets', [
                    'client_ip' => $clientIp,
                    'total_requested' => count($updates),
                    'total_updated' => $updatedCount,
                    'errors_count' => count($errors),
                    'contract_ids_affected' => array_unique(array_column($updatedRecords, 'contract_id')),
                ]);
            }

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "updated_count" => $updatedCount,
                    "errors" => $errors,
                    "message" => "Aggiornati $updatedCount record. I trigger sincronizzeranno specific_datas automaticamente."
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            SystemLogService::externalApi()->error('Error in contract-data-overview bulkUpdate', [
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
     */
    public function show(Request $request, $id)
    {
        $clientIp = $this->getClientIdentifier($request);

        if (!$this->verifyApiKey($request)) {
            SystemLogService::externalApi()->warning('Unauthorized access attempt to show record', [
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
            $record = DB::table('contract_data_overview')->where('id', $id)->first();

            if (!$record) {
                return response()->json([
                    "response" => "ko",
                    "status" => "404",
                    "body" => ["error" => "Record not found"]
                ], 404);
            }

            SystemLogService::externalApi()->debug('Single record accessed via Google Sheets', [
                'client_ip' => $clientIp,
                'record_id' => $id,
                'contract_id' => $record->contract_id,
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["data" => $record]
            ]);

        } catch (\Exception $e) {
            SystemLogService::externalApi()->error('Error in contract-data-overview show', [
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