<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * Get wallet information for authenticated user
     * Only clients (role_id = 3) can access their wallet
     * 
     * Calculates PV bloccati from cart_items table
     */
    public function getWallet()
    {
        try {
            Log::info('=== WALLET REQUEST START ===');
            
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    "response" => "error",
                    "status" => "401",
                    "message" => "User not authenticated"
                ]);
            }

            Log::info('User authenticated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role_id' => $user->role_id
            ]);

            // Only clients (role_id = 3) can access wallet
            if ($user->role_id != 3) {
                return response()->json([
                    "response" => "error",
                    "status" => "403",
                    "message" => "Wallet is available only for clients"
                ]);
            }

            // Calculate PV bloccati from cart_items
            $pvBlocked = 0;
            if (DB::getSchemaBuilder()->hasTable('cart_items')) {
                // Check which column exists (pv_bloccati or pv_temporanei)
                $columnName = DB::getSchemaBuilder()->hasColumn('cart_items', 'pv_bloccati') 
                    ? 'pv_bloccati' 
                    : 'pv_temporanei';
                
                $pvBlocked = DB::table('cart_items')
                    ->where('user_id', $user->id)
                    ->sum($columnName);
                    
                $pvBlocked = $pvBlocked ? (int)$pvBlocked : 0;
                Log::info("Cart PV blocked from cart_items.{$columnName}", ['pv_blocked' => $pvBlocked]);
            }

            // Get data from user table
            $pvMaturati = (int)($user->punti_valore_maturati ?? 0);
            $pvBonus = (int)($user->punti_bonus ?? 0);
            $pvSpesi = (int)($user->punti_spesi ?? 0);
            
            // Calculate derived values
            $pvTotali = $pvMaturati + $pvBonus;
            $pvDisponibili = max(0, $pvTotali - $pvBlocked - $pvSpesi);

            $walletData = [
                'pv_maturati' => $pvMaturati,
                'pv_bonus' => $pvBonus,
                'pv_totali' => $pvTotali,
                'pv_bloccati' => $pvBlocked,
                'pv_disponibili' => $pvDisponibili,
                'pv_spesi' => $pvSpesi,
            ];

            Log::info('Wallet data prepared', $walletData);
            Log::info('=== WALLET REQUEST SUCCESS ===');

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => ["risposta" => $walletData]
            ]);

        } catch (\Exception $e) {
            Log::error('=== WALLET REQUEST ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Get wallet summary with active cart info
     */
    public function getWalletSummary()
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->role_id != 3) {
                return response()->json([
                    "response" => "error",
                    "status" => "403",
                    "message" => "Access denied"
                ]);
            }

            // Get wallet data
            $walletResponse = $this->getWallet();
            $walletContent = json_decode($walletResponse->content(), true);

            if ($walletContent['response'] !== 'ok') {
                return $walletResponse;
            }

            // Get cart info from cart_items
            $cartInfo = null;
            if (DB::getSchemaBuilder()->hasTable('cart_items')) {
                $cartItems = DB::table('cart_items')
                    ->where('user_id', $user->id)
                    ->get();
                    
                if ($cartItems->count() > 0) {
                    $columnName = DB::getSchemaBuilder()->hasColumn('cart_items', 'pv_bloccati') 
                        ? 'pv_bloccati' 
                        : 'pv_temporanei';
                        
                    $totalPv = $cartItems->sum($columnName);
                    
                    $cartInfo = [
                        'items_count' => $cartItems->count(),
                        'total_pv' => (int)$totalPv
                    ];
                }
            }

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => [
                        'wallet' => $walletContent['body']['risposta'],
                        'active_cart' => $cartInfo
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting wallet summary: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Get transaction history (orders) with pagination
     */
    public function getTransactionHistory(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->role_id != 3) {
                return response()->json([
                    "response" => "error",
                    "status" => "403",
                    "message" => "Access denied"
                ]);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            if (!DB::getSchemaBuilder()->hasTable('orders')) {
                return response()->json([
                    "response" => "ok",
                    "status" => "200",
                    "body" => [
                        "risposta" => [
                            'current_page' => 1,
                            'data' => [],
                            'total' => 0,
                            'per_page' => $perPage,
                            'last_page' => 1
                        ]
                    ]
                ]);
            }

            $ordersQuery = DB::table('orders')
                ->where('orders.user_id', $user->id)
                ->select(
                    'orders.id',
                    'orders.order_number',
                    'orders.total_pv',
                    'orders.order_date',
                    'orders.created_at'
                );

            if (DB::getSchemaBuilder()->hasTable('order_statuses')) {
                $ordersQuery->leftJoin('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id')
                    ->addSelect('order_statuses.status_name');
            }

            if (DB::getSchemaBuilder()->hasTable('payment_methods')) {
                $ordersQuery->leftJoin('payment_methods', 'orders.payment_method_id', '=', 'payment_methods.id')
                    ->addSelect('payment_methods.metodo as payment_method');
            }

            $ordersQuery->orderBy('orders.created_at', 'desc');
            $orders = $ordersQuery->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => [
                        'current_page' => $orders->currentPage(),
                        'data' => $orders->items(),
                        'total' => $orders->total(),
                        'per_page' => $orders->perPage(),
                        'last_page' => $orders->lastPage()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting transaction history: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Update user points after purchase
     */
    public function updatePointsAfterPurchase(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'points_used' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400",
                    "errors" => $validator->errors()
                ]);
            }

            $user = User::findOrFail($request->user_id);
            $user->punti_spesi = ($user->punti_spesi ?? 0) + $request->points_used;
            $user->save();

            Log::info('Points updated', [
                'user_id' => $user->id,
                'points_used' => $request->points_used
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => [
                        'punti_spesi' => $user->punti_spesi,
                        'punti_utilizzati' => $request->points_used
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating points: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }

    /**
     * Add bonus points to user (Admin only)
     */
    public function addBonusPoints(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'bonus_points' => 'required|integer|min:0',
                'reason' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "response" => "error",
                    "status" => "400",
                    "errors" => $validator->errors()
                ]);
            }

            $currentUser = Auth::user();
            if (!in_array($currentUser->role_id, [1, 5])) {
                return response()->json([
                    "response" => "error",
                    "status" => "403",
                    "message" => "Only administrators can add bonus points"
                ]);
            }

            $user = User::findOrFail($request->user_id);
            $user->punti_bonus = ($user->punti_bonus ?? 0) + $request->bonus_points;
            $user->save();

            Log::info('Bonus points added', [
                'user_id' => $user->id,
                'bonus_points' => $request->bonus_points,
                'reason' => $request->reason ?? 'Manual',
                'added_by' => $currentUser->id
            ]);

            return response()->json([
                "response" => "ok",
                "status" => "200",
                "body" => [
                    "risposta" => [
                        'punti_bonus_totali' => $user->punti_bonus,
                        'punti_aggiunti' => $request->bonus_points
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding bonus points: ' . $e->getMessage());
            return response()->json([
                "response" => "error",
                "status" => "500",
                "message" => "Server error"
            ]);
        }
    }
}