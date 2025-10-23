<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class WalletController extends Controller
{
    /**
     * Get authenticated user's wallet information (salvadanaio)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWallet(Request $request)
    {
        try {
            // Get authenticated user ID from JWT token
            $userId = auth()->user()->id;
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get user data
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get PV bloccati from active cart
            $pvBlocked = DB::table('carts')
                ->where('user_id', $userId)
                ->where('cart_status_id', 1) // attivo
                ->sum('total_pv') ?? 0;

            // Get data directly from user table
            $pvMaturati = $user->punti_valore_maturati ?? 0;
            $pvBonus = $user->punti_bonus ?? 0;
            $pvSpesi = $user->punti_spesi ?? 0;
            
            // Calculate derived values
            $pvTotali = $pvMaturati + $pvBonus;
            $pvDisponibili = $pvTotali - $pvBlocked - $pvSpesi;

            $walletData = [
                'pv_maturati' => (int)$pvMaturati,
                'pv_bonus' => (int)$pvBonus,
                'pv_totali' => (int)$pvTotali,
                'pv_bloccati' => (int)$pvBlocked,
                'pv_disponibili' => (int)$pvDisponibili,
                'pv_spesi' => (int)$pvSpesi,
            ];

            return response()->json([
                'success' => true,
                'data' => $walletData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving wallet data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet summary with active cart info
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWalletSummary(Request $request)
    {
        try {
            $userId = auth()->user()->id;
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get wallet data
            $walletResponse = $this->getWallet($request);
            $walletData = json_decode($walletResponse->content(), true);

            if (!$walletData['success']) {
                return $walletResponse;
            }

            // Get active cart
            $activeCart = DB::table('carts')
                ->where('user_id', $userId)
                ->where('cart_status_id', 1) // attivo
                ->first();

            $summary = [
                'wallet' => $walletData['data'],
                'active_cart' => $activeCart ? [
                    'id' => $activeCart->id,
                    'total_pv' => (int)$activeCart->total_pv,
                    'items_count' => DB::table('cart_items')
                        ->where('cart_id', $activeCart->id)
                        ->count()
                ] : null
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving wallet summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction history (orders)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionHistory(Request $request)
    {
        try {
            $userId = auth()->user()->id;
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            // Get orders with pagination
            $orders = DB::table('orders')
                ->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id')
                ->join('payment_methods', 'orders.payment_method_id', '=', 'payment_methods.id')
                ->where('orders.user_id', $userId)
                ->select(
                    'orders.id',
                    'orders.order_number as codice_ordine',
                    'orders.order_date as data_ordine',
                    'orders.total_pv as totale_pv',
                    'order_statuses.status_name as stato_ordine',
                    'payment_methods.metodo as metodo_pagamento'
                )
                ->orderBy('orders.order_date', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => $orders->currentPage(),
                    'data' => $orders->items(),
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'last_page' => $orders->lastPage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}