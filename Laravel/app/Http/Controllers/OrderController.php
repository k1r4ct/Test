<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\CartStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\payment_mode;
use App\Models\User;
use App\Models\notification;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * OrderController
 * 
 * Handles order operations:
 * 
 * Customer:
 * - Checkout (create order from cart)
 * - View order history
 * - View order detail
 * 
 * Backoffice:
 * - View all pending orders
 * - Take order in charge
 * - Fulfill order items (add redemption codes)
 * - Complete order
 * - Cancel order
 */
class OrderController extends Controller
{
    // ==================== CUSTOMER ENDPOINTS ====================

    /**
     * Checkout: Create order from active cart.
     * 
     * POST /api/ecommerce/checkout
     * 
     * Body:
     * - customer_message: optional message from customer
     */
    public function checkout(Request $request)
    {
        try {
            $user = Auth::user();

            // Get active cart items
            $activeStatus = CartStatus::where('status_name', 'attivo')->first();
            $cartItems = CartItem::where('user_id', $user->id)
                ->where('cart_status_id', $activeStatus->id)
                ->with('article')
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => 'Cart is empty',
                ], 400);
            }

            // Calculate total PV
            $totalPv = $cartItems->sum('pv_bloccati');

            // Verify user still has enough PV (they should, since they're blocked)
            if ($user->pv_totali < $totalPv) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => 'Insufficient PV balance',
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Get status IDs
                $pendingOrderStatus = OrderStatus::where('status_name', 'in_attesa')->first();
                $pendingCartStatus = CartStatus::where('status_name', 'in_attesa_di_pagamento')->first();
                
                // Get PV payment mode
                $pvPaymentMode = payment_mode::where('tipo_pagamento', 'like', '%Punti Valore%')
                    ->orWhere('tipo_pagamento', 'like', '%PV%')
                    ->first();

                if (!$pendingOrderStatus || !$pendingCartStatus) {
                    throw new \Exception('Order/Cart status configuration error');
                }

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_pv' => $totalPv,
                    'order_status_id' => $pendingOrderStatus->id,
                    'priority' => Order::PRIORITY_NORMAL,
                    'payment_method_id' => $pvPaymentMode?->id ?? 1,
                    'customer_message' => $request->customer_message,
                    'customer_email' => $user->email,
                    'customer_name' => trim($user->name . ' ' . ($user->cognome ?? '')),
                    'order_date' => now(),
                ]);

                // Create order items from cart
                foreach ($cartItems as $cartItem) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'article_id' => $cartItem->article_id,
                        'article_name_snapshot' => $cartItem->article->article_name,
                        'article_sku_snapshot' => $cartItem->article->sku,
                        'quantity' => $cartItem->quantity,
                        'pv_unit_price' => $cartItem->article->pv_price,
                        'pv_total_price' => $cartItem->pv_bloccati,
                        'item_status' => OrderItem::STATUS_PENDING,
                    ]);

                    // Update cart item status to pending payment
                    $cartItem->update(['cart_status_id' => $pendingCartStatus->id]);
                }

                // Send notification to backoffice users
                $this->notifyBackoffice($order, $user);

                DB::commit();

                // Load order with items for response
                $order->load(['orderItems', 'orderStatus']);

                return response()->json([
                    'response' => 'ok',
                    'status' => '200',
                    'body' => [
                        'message' => 'Order created successfully',
                        'order' => [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'total_pv' => $order->total_pv,
                            'status' => $order->orderStatus->status_name,
                            'items_count' => $order->orderItems->count(),
                            'created_at' => $order->created_at->format('d/m/Y H:i'),
                        ],
                    ],
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            SystemLogService::ecommerce()->error("Checkout failed", [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ], $e);

            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Checkout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's order history.
     * 
     * GET /api/ecommerce/orders
     * 
     * Query params:
     * - status: Filter by status name
     * - per_page: Items per page (default 10)
     */
    public function getOrders(Request $request)
    {
        try {
            $user = Auth::user();

            $query = Order::where('user_id', $user->id)
                ->with(['orderStatus', 'orderItems'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->filled('status')) {
                $query->whereHas('orderStatus', function ($q) use ($request) {
                    $q->where('status_name', $request->status);
                });
            }

            $perPage = min($request->get('per_page', 10), 50);
            $orders = $query->paginate($perPage);

            $items = collect($orders->items())->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_pv' => $order->total_pv,
                    'formatted_total_pv' => $order->getFormattedTotalPv(),
                    'status' => $order->orderStatus->status_name,
                    'status_label' => $order->getStatusLabel(),
                    'items_count' => $order->orderItems->count(),
                    'fulfilled_count' => $order->getFulfilledItemsCount(),
                    'fulfillment_progress' => $order->getFulfillmentProgress(),
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                    'processed_at' => $order->processed_at?->format('d/m/Y H:i'),
                ];
            });

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'orders' => $items,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order detail.
     * 
     * GET /api/ecommerce/orders/{orderId}
     */
    public function getOrderDetail(int $orderId)
    {
        try {
            $user = Auth::user();

            $order = Order::with([
                'orderStatus',
                'orderItems.article',
                'processedBy',
            ])->find($orderId);

            if (!$order) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Order not found',
                ], 404);
            }

            // Check ownership (unless admin/backoffice)
            if ($order->user_id !== $user->id && !in_array($user->role_id, [1, 5])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied',
                ], 403);
            }

            $items = $order->orderItems->map(function ($item) use ($user) {
                $data = [
                    'id' => $item->id,
                    'article_name' => $item->getArticleName(),
                    'article_sku' => $item->getArticleSku(),
                    'quantity' => $item->quantity,
                    'pv_unit_price' => $item->pv_unit_price,
                    'pv_total_price' => $item->pv_total_price,
                    'formatted_unit_price' => $item->getFormattedUnitPrice(),
                    'formatted_total_price' => $item->getFormattedTotalPrice(),
                    'status' => $item->item_status,
                    'status_label' => $item->getStatusLabel(),
                    'fulfilled_at' => $item->fulfilled_at?->format('d/m/Y H:i'),
                ];

                // Only show redemption code to the order owner
                if ($order->user_id === $user->id && $item->redemption_code) {
                    $data['redemption_code'] = $item->redemption_code;
                }

                // Show customer note if present
                if ($item->customer_note) {
                    $data['customer_note'] = $item->customer_note;
                }

                return $data;
            });

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total_pv' => $order->total_pv,
                        'formatted_total_pv' => $order->getFormattedTotalPv(),
                        'status' => $order->orderStatus->status_name,
                        'status_label' => $order->getStatusLabel(),
                        'priority' => $order->priority,
                        'priority_label' => $order->getPriorityLabel(),
                        'customer_message' => $order->customer_message,
                        'fulfillment_progress' => $order->getFulfillmentProgress(),
                        'created_at' => $order->created_at->format('d/m/Y H:i'),
                        'processing_started_at' => $order->processing_started_at?->format('d/m/Y H:i'),
                        'processed_at' => $order->processed_at?->format('d/m/Y H:i'),
                        'processed_by' => $order->processedBy ? [
                            'name' => $order->processedBy->name . ' ' . $order->processedBy->cognome,
                        ] : null,
                    ],
                    'items' => $items,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading order: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== BACKOFFICE ENDPOINTS ====================

    /**
     * Get all orders for backoffice processing.
     * 
     * GET /api/ecommerce/admin/orders
     * 
     * Query params:
     * - status: Filter by status
     * - priority: Filter by priority
     * - assigned_to_me: Only my assigned orders
     * - per_page: Items per page
     */
    public function getAllOrders(Request $request)
    {
        try {
            $user = Auth::user();

            // Check permission
            if (!in_array($user->role_id, [1, 5])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied',
                ], 403);
            }

            $query = Order::with(['orderStatus', 'orderItems', 'user', 'processedBy'])
                ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
                ->orderBy('created_at', 'asc');

            // Filter by status
            if ($request->filled('status')) {
                $query->whereHas('orderStatus', function ($q) use ($request) {
                    $q->where('status_name', $request->status);
                });
            }

            // Filter by priority
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            // Filter assigned to current user
            if ($request->filled('assigned_to_me') && $request->assigned_to_me) {
                $query->where('processed_by_user_id', $user->id);
            }

            // Only pending orders by default
            if (!$request->filled('status') && !$request->filled('all')) {
                $query->whereHas('orderStatus', function ($q) {
                    $q->whereIn('status_name', ['in_attesa', 'in_lavorazione']);
                });
            }

            $perPage = min($request->get('per_page', 20), 100);
            $orders = $query->paginate($perPage);

            $items = collect($orders->items())->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_pv' => $order->total_pv,
                    'formatted_total_pv' => $order->getFormattedTotalPv(),
                    'status' => $order->orderStatus->status_name,
                    'status_label' => $order->getStatusLabel(),
                    'priority' => $order->priority,
                    'priority_label' => $order->getPriorityLabel(),
                    'priority_class' => $order->getPriorityClass(),
                    'customer' => [
                        'id' => $order->user_id,
                        'name' => $order->customer_name ?? ($order->user ? $order->user->name . ' ' . $order->user->cognome : 'N/A'),
                        'email' => $order->customer_email ?? $order->user?->email,
                    ],
                    'items_count' => $order->orderItems->count(),
                    'pending_items' => $order->getPendingItemsCount(),
                    'fulfillment_progress' => $order->getFulfillmentProgress(),
                    'assigned_to' => $order->processedBy ? [
                        'id' => $order->processedBy->id,
                        'name' => $order->processedBy->name . ' ' . $order->processedBy->cognome,
                    ] : null,
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                    'processing_started_at' => $order->processing_started_at?->format('d/m/Y H:i'),
                ];
            });

            // Get statistics
            $stats = [
                'pending' => Order::whereHas('orderStatus', fn($q) => $q->where('status_name', 'in_attesa'))->count(),
                'processing' => Order::whereHas('orderStatus', fn($q) => $q->where('status_name', 'in_lavorazione'))->count(),
                'completed_today' => Order::whereHas('orderStatus', fn($q) => $q->where('status_name', 'completato'))
                    ->whereDate('processed_at', today())->count(),
            ];

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'orders' => $items,
                    'statistics' => $stats,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Take an order in charge (start processing).
     * 
     * POST /api/ecommerce/admin/orders/{orderId}/process
     */
    public function startProcessing(int $orderId)
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 5])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied',
                ], 403);
            }

            $order = Order::with('orderStatus')->find($orderId);

            if (!$order) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Order not found',
                ], 404);
            }

            // Check if already being processed by someone else
            if ($order->processed_by_user_id && $order->processed_by_user_id !== $user->id) {
                $processor = User::find($order->processed_by_user_id);
                return response()->json([
                    'response' => 'error',
                    'status' => '409',
                    'message' => 'Order is already being processed by ' . ($processor ? $processor->name : 'another user'),
                ], 409);
            }

            // Update status to "in_lavorazione"
            $processingStatus = OrderStatus::where('status_name', 'in_lavorazione')->first();
            
            $order->startProcessing($user->id);
            
            if ($processingStatus) {
                $order->update(['order_status_id' => $processingStatus->id]);
            }

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Order taken in charge',
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => 'in_lavorazione',
                        'processing_started_at' => $order->processing_started_at->format('d/m/Y H:i'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error processing order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fulfill an order item (add redemption code).
     * 
     * POST /api/ecommerce/admin/orders/{orderId}/items/{itemId}/fulfill
     * 
     * Body:
     * - redemption_code: required - the gift card code
     * - customer_note: optional - note to show to customer
     */
    public function fulfillItem(Request $request, int $orderId, int $itemId)
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 5])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'redemption_code' => 'required|string|min:5|max:255',
                'customer_note' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $order = Order::find($orderId);
            
            if (!$order) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Order not found',
                ], 404);
            }

            $item = OrderItem::where('id', $itemId)
                ->where('order_id', $orderId)
                ->first();

            if (!$item) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Order item not found',
                ], 404);
            }

            if ($item->isFulfilled()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => 'Item already fulfilled',
                ], 400);
            }

            // Fulfill the item
            $item->fulfill(
                $request->redemption_code,
                $user->id,
                $request->customer_note
            );

            // Check if all items are fulfilled
            $order->refresh();
            $allFulfilled = $order->areAllItemsFulfilled();

            // If all fulfilled, complete the order
            if ($allFulfilled) {
                $order->markAsProcessed('All items have been fulfilled.');
            }

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Item fulfilled successfully',
                    'item' => [
                        'id' => $item->id,
                        'status' => $item->item_status,
                        'fulfilled_at' => $item->fulfilled_at->format('d/m/Y H:i'),
                    ],
                    'order' => [
                        'id' => $order->id,
                        'all_fulfilled' => $allFulfilled,
                        'fulfillment_progress' => $order->getFulfillmentProgress(),
                        'status' => $order->fresh()->orderStatus->status_name,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error fulfilling item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an order.
     * 
     * POST /api/ecommerce/admin/orders/{orderId}/cancel
     * 
     * Body:
     * - reason: required - cancellation reason
     */
    public function cancelOrder(Request $request, int $orderId)
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 5])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $order = Order::with('orderStatus')->find($orderId);

            if (!$order) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Order not found',
                ], 404);
            }

            // Can't cancel completed orders
            if ($order->isCompleted()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => 'Cannot cancel completed orders',
                ], 400);
            }

            // Cancel the order (this triggers model events that handle PV release)
            $order->cancel($request->reason);

            // Cancel all pending order items
            $order->orderItems()
                ->whereIn('item_status', [OrderItem::STATUS_PENDING, OrderItem::STATUS_PROCESSING])
                ->each(function ($item) use ($request) {
                    $item->cancel($request->reason);
                });

            // Notify customer
            $this->notifyCustomerCancellation($order, $request->reason);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Order cancelled successfully',
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => 'annullato',
                        'cancellation_reason' => $order->cancellation_reason,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error cancelling order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order detail for backoffice (includes admin notes, internal info).
     * 
     * GET /api/ecommerce/admin/orders/{orderId}
     */
    public function getAdminOrderDetail(int $orderId)
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 5])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied',
                ], 403);
            }

            $order = Order::with([
                'orderStatus',
                'orderItems.article',
                'orderItems.fulfilledBy',
                'user',
                'processedBy',
            ])->find($orderId);

            if (!$order) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Order not found',
                ], 404);
            }

            $items = $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'article_id' => $item->article_id,
                    'article_name' => $item->getArticleName(),
                    'article_sku' => $item->getArticleSku(),
                    'quantity' => $item->quantity,
                    'pv_unit_price' => $item->pv_unit_price,
                    'pv_total_price' => $item->pv_total_price,
                    'status' => $item->item_status,
                    'status_label' => $item->getStatusLabel(),
                    'redemption_code' => $item->redemption_code,
                    'fulfilled_at' => $item->fulfilled_at?->format('d/m/Y H:i'),
                    'fulfilled_by' => $item->fulfilledBy ? [
                        'id' => $item->fulfilledBy->id,
                        'name' => $item->fulfilledBy->name . ' ' . $item->fulfilledBy->cognome,
                    ] : null,
                    'internal_note' => $item->internal_note,
                    'customer_note' => $item->customer_note,
                ];
            });

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total_pv' => $order->total_pv,
                        'formatted_total_pv' => $order->getFormattedTotalPv(),
                        'status' => $order->orderStatus->status_name,
                        'status_label' => $order->getStatusLabel(),
                        'priority' => $order->priority,
                        'priority_label' => $order->getPriorityLabel(),
                        'customer' => [
                            'id' => $order->user_id,
                            'name' => $order->customer_name,
                            'email' => $order->customer_email,
                        ],
                        'customer_message' => $order->customer_message,
                        'admin_notes' => $order->admin_notes,
                        'cancellation_reason' => $order->cancellation_reason,
                        'fulfillment_progress' => $order->getFulfillmentProgress(),
                        'created_at' => $order->created_at->format('d/m/Y H:i'),
                        'processing_started_at' => $order->processing_started_at?->format('d/m/Y H:i'),
                        'processed_at' => $order->processed_at?->format('d/m/Y H:i'),
                        'cancelled_at' => $order->cancelled_at?->format('d/m/Y H:i'),
                        'assigned_to' => $order->processedBy ? [
                            'id' => $order->processedBy->id,
                            'name' => $order->processedBy->name . ' ' . $order->processedBy->cognome,
                        ] : null,
                    ],
                    'items' => $items,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add admin note to order.
     * 
     * POST /api/ecommerce/admin/orders/{orderId}/note
     */
    public function addAdminNote(Request $request, int $orderId)
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role_id, [1, 5])) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Access denied',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'note' => 'required|string|min:1|max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Order not found',
                ], 404);
            }

            $order->addAdminNote($request->note, $user->id);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Note added',
                    'admin_notes' => $order->admin_notes,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error adding note: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Send notification to backoffice users about new order.
     */
    private function notifyBackoffice(Order $order, User $customer): void
    {
        try {
            // Get all backoffice users (role 1 and 5)
            $backofficeUsers = User::whereIn('role_id', [1, 5])->get();

            foreach ($backofficeUsers as $boUser) {
                notification::create([
                    'user_id' => $boUser->id,
                    'title' => 'Nuovo ordine e-commerce',
                    'message' => "Ordine #{$order->order_number} da {$customer->name} {$customer->cognome} - Totale: {$order->total_pv} PV",
                    'type' => 'ecommerce_order',
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'priority' => $order->priority === Order::PRIORITY_URGENT ? 'high' : 'normal',
                ]);
            }

            // TODO: Send email notification if configured
            // Mail::to(config('ecommerce.notifications.admin_emails'))->send(new NewOrderNotification($order));

        } catch (\Exception $e) {
            SystemLogService::ecommerce()->warning("Failed to send backoffice notification", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to customer about order cancellation.
     */
    private function notifyCustomerCancellation(Order $order, string $reason): void
    {
        try {
            notification::create([
                'user_id' => $order->user_id,
                'title' => 'Ordine annullato',
                'message' => "Il tuo ordine #{$order->order_number} è stato annullato. I tuoi PV sono stati ripristinati.",
                'type' => 'ecommerce_order',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'priority' => 'normal',
            ]);

            // TODO: Send email notification
            // Mail::to($order->customer_email)->send(new OrderCancelledNotification($order, $reason));

        } catch (\Exception $e) {
            SystemLogService::ecommerce()->warning("Failed to send cancellation notification", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
