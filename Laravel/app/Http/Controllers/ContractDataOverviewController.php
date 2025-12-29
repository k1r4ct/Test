<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractDataOverviewController extends Controller
{
    /**
     * Secret key for API authentication
     * TODO: Move to .env file as CONTRACT_DATA_API_KEY
     */
    private $apiSecret = 'SempreChiaro2024SecretKey!';

    /**
     * Verify API key from header or query param
     */
    private function verifyApiKey(Request $request)
    {
        $providedKey = $request->header('X-API-Key') ?? $request->input('api_key');
        return $providedKey === $this->apiSecret;
    }

    /**
     * Get all contract data overview records
     * Optionally filter by contract_ids
     */
    public function index(Request $request)
    {
        if (!$this->verifyApiKey($request)) {
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
            if ($request->has('contract_ids')) {
                $contractIds = explode(',', $request->input('contract_ids'));
                $contractIds = array_map('trim', $contractIds);
                $contractIds = array_filter($contractIds, 'is_numeric');
                
                if (!empty($contractIds)) {
                    $query->whereIn('contract_id', $contractIds);
                }
            }

            // Limit results (default 5000, max 10000)
            $limit = $request->has('limit') 
                ? min((int)$request->input('limit'), 10000) 
                : 5000;
            $query->limit($limit);

            $data = $query->get();

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "count" => $data->count(),
                    "data" => $data
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('ContractDataOverview index error: ' . $e->getMessage());
            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Bulk update multiple records
     * Expects JSON body with "updates" array
     */
    public function bulkUpdate(Request $request)
    {
        if (!$this->verifyApiKey($request)) {
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

            DB::beginTransaction();

            foreach ($updates as $index => $update) {
                if (!isset($update['id'])) {
                    $errors[] = "Row $index: Missing id";
                    continue;
                }

                $id = (int)$update['id'];
                $updateData = [];
                
                // Handle risposta_tipo_bool
                if (array_key_exists('risposta_tipo_bool', $update)) {
                    $val = $update['risposta_tipo_bool'];
                    $updateData['risposta_tipo_bool'] = ($val === '' || $val === null) 
                        ? null 
                        : (int)$val;
                }
                
                // Handle risposta_tipo_numero
                if (array_key_exists('risposta_tipo_numero', $update)) {
                    $val = $update['risposta_tipo_numero'];
                    $updateData['risposta_tipo_numero'] = ($val === '' || $val === null) 
                        ? null 
                        : (float)$val;
                }
                
                // Handle risposta_tipo_stringa
                if (array_key_exists('risposta_tipo_stringa', $update)) {
                    $val = $update['risposta_tipo_stringa'];
                    $updateData['risposta_tipo_stringa'] = ($val === '') 
                        ? null 
                        : $val;
                }

                if (!empty($updateData)) {
                    $affected = DB::table('contract_data_overview')
                        ->where('id', $id)
                        ->update($updateData);
                    
                    if ($affected > 0) {
                        $updatedCount++;
                        Log::info("ContractDataOverview: Updated record ID $id", $updateData);
                    }
                }
            }

            DB::commit();

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
            Log::error('ContractDataOverview bulkUpdate error: ' . $e->getMessage());
            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Get single record by ID
     */
    public function show(Request $request, $id)
    {
        if (!$this->verifyApiKey($request)) {
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

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["data" => $record]
            ]);

        } catch (\Exception $e) {
            Log::error('ContractDataOverview show error: ' . $e->getMessage());
            return response()->json([
                "response" => "ko",
                "status" => "500",
                "body" => ["error" => $e->getMessage()]
            ], 500);
        }
    }
}