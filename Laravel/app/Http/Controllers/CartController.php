<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CartItem;
use App\Models\CartStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * CartController
 * 
 * Handles shopping cart operations:
 * - View cart with items and totals
 * - Add items (blocks PV)
 * - Update quantity
 * - Remove items (releases PV)
 * - Clear entire cart
 */
class CartController extends Controller
{
    /**
     * Get the current user's active cart.
     * 
     * GET /api/ecommerce/cart
     */
    public function getCart()
    {
        try {
            $user = Auth::user();
            $cartItems = $user->getActiveCart();

            $items = $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'article_id' => $item->article_id,
                    'article_name' => $item->article?->article_name,
                    'article_sku' => $item->article?->sku,
                    'thumbnail_url' => $item->article?->thumbnail?->getUrl(),
                    'is_digital' => $item->article?->is_digital,
                    'pv_unit_price' => $item->article?->pv_price,
                    'euro_unit_price' => $item->article?->euro_price,
                    'quantity' => $item->quantity,
                    'pv_total' => $item->pv_bloccati,
                    'category_name' => $item->article?->category?->category_name,
                    'added_at' => $item->created_at->format('d/m/Y H:i'),
                    'expires_at' => $item->updated_at->addMinutes(config('ecommerce.cart.expiration_minutes', 30))->format('d/m/Y H:i'),
                ];
            });

            $totalPv = $cartItems->sum('pv_bloccati');
            $totalItems = $cartItems->sum('quantity');

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'cart' => [
                        'items' => $items,
                        'total_pv' => $totalPv,
                        'total_items' => $totalItems,
                        'items_count' => $cartItems->count(),
                    ],
                    'user_balance' => [
                        'pv_totali' => $user->pv_totali,
                        'pv_bloccati' => $user->pv_bloccati,
                        'pv_disponibili' => $user->pv_disponibili,
                        'punti_bonus' => $user->punti_bonus ?? 0,
                        'punti_maturati' => $user->punti_valore_maturati ?? 0,
                    ],
                    'cart_expiration_minutes' => config('ecommerce.cart.expiration_minutes', 30),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add an item to the cart.
     * 
     * POST /api/ecommerce/cart/add
     * 
     * Body:
     * - article_id: required
     * - quantity: optional (default 1)
     */
    public function addToCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'article_id' => 'required|integer|exists:articles,id',
                'quantity' => 'nullable|integer|min:1|max:' . config('ecommerce.cart.max_quantity_per_item', 10),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = Auth::user();
            $articleId = $request->article_id;
            $quantity = $request->get('quantity', 1);

            // Check article exists and is available
            $article = Article::find($articleId);
            
            if (!$article || !$article->available) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => 'Article not available',
                ], 400);
            }

            // Check user can see this article
            if (!$article->isVisibleToUser($user)) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'You do not have access to this article',
                ], 403);
            }

            // Check stock (for non-digital products)
            if (!$article->is_digital) {
                $stock = $article->stock()->first();
                if ($stock && $stock->quantity < $quantity) {
                    return response()->json([
                        'response' => 'error',
                        'status' => '400',
                        'message' => 'Insufficient stock. Available: ' . $stock->quantity,
                    ], 400);
                }
            }

            // Check max items in cart
            $currentCartCount = $user->getActiveCart()->count();
            $maxItems = config('ecommerce.cart.max_items', 10);
            
            $existingItem = $user->cartItems()
                ->where('article_id', $articleId)
                ->active()
                ->first();

            if (!$existingItem && $currentCartCount >= $maxItems) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => "Maximum cart items limit reached ({$maxItems})",
                ], 400);
            }

            // Block PV and add to cart (User model handles the logic)
            $cartItem = $user->blockPv($articleId, $quantity);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Item added to cart',
                    'cart_item' => [
                        'id' => $cartItem->id,
                        'article_id' => $cartItem->article_id,
                        'article_name' => $article->article_name,
                        'quantity' => $cartItem->quantity,
                        'pv_bloccati' => $cartItem->pv_bloccati,
                    ],
                    'user_balance' => [
                        'pv_totali' => $user->fresh()->pv_totali,
                        'pv_bloccati' => $user->fresh()->pv_bloccati,
                        'pv_disponibili' => $user->fresh()->pv_disponibili,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Handle specific exceptions
            if (str_contains($message, 'Insufficient PV')) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => $message,
                ], 400);
            }

            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error adding to cart: ' . $message,
            ], 500);
        }
    }

    /**
     * Update cart item quantity.
     * 
     * PUT /api/ecommerce/cart/update/{cartItemId}
     * 
     * Body:
     * - quantity: required (0 to remove)
     */
    public function updateQuantity(Request $request, int $cartItemId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:0|max:' . config('ecommerce.cart.max_quantity_per_item', 10),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = Auth::user();
            $newQuantity = $request->quantity;

            // Find cart item
            $cartItem = CartItem::where('id', $cartItemId)
                ->where('user_id', $user->id)
                ->active()
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Cart item not found',
                ], 404);
            }

            // If quantity is 0, remove the item
            if ($newQuantity === 0) {
                $cartItem->delete(); // This releases PV via model event
                
                return response()->json([
                    'response' => 'ok',
                    'status' => '200',
                    'body' => [
                        'message' => 'Item removed from cart',
                        'user_balance' => [
                            'pv_totali' => $user->fresh()->pv_totali,
                            'pv_bloccati' => $user->fresh()->pv_bloccati,
                            'pv_disponibili' => $user->fresh()->pv_disponibili,
                        ],
                    ],
                ]);
            }

            // Update quantity using User model method
            $updatedItem = $user->updateCartItemQuantity($cartItemId, $newQuantity);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Cart updated',
                    'cart_item' => [
                        'id' => $updatedItem->id,
                        'article_id' => $updatedItem->article_id,
                        'quantity' => $updatedItem->quantity,
                        'pv_bloccati' => $updatedItem->pv_bloccati,
                    ],
                    'user_balance' => [
                        'pv_totali' => $user->fresh()->pv_totali,
                        'pv_bloccati' => $user->fresh()->pv_bloccati,
                        'pv_disponibili' => $user->fresh()->pv_disponibili,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'Insufficient PV')) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'message' => $message,
                ], 400);
            }

            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error updating cart: ' . $message,
            ], 500);
        }
    }

    /**
     * Remove an item from the cart.
     * 
     * DELETE /api/ecommerce/cart/remove/{cartItemId}
     */
    public function removeItem(int $cartItemId)
    {
        try {
            $user = Auth::user();

            // Find and delete (releases PV automatically via model event)
            $deleted = $user->releasePv($cartItemId);

            if (!$deleted) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Cart item not found',
                ], 404);
            }

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Item removed from cart',
                    'user_balance' => [
                        'pv_totali' => $user->fresh()->pv_totali,
                        'pv_bloccati' => $user->fresh()->pv_bloccati,
                        'pv_disponibili' => $user->fresh()->pv_disponibili,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error removing item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear the entire cart.
     * 
     * DELETE /api/ecommerce/cart/clear
     */
    public function clearCart()
    {
        try {
            $user = Auth::user();
            
            $itemsCount = $user->getActiveCart()->count();
            $user->clearActiveCart();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => "Cart cleared ({$itemsCount} items removed)",
                    'user_balance' => [
                        'pv_totali' => $user->fresh()->pv_totali,
                        'pv_bloccati' => $user->fresh()->pv_bloccati,
                        'pv_disponibili' => $user->fresh()->pv_disponibili,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error clearing cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cart summary (for header badge, etc.)
     * 
     * GET /api/ecommerce/cart/summary
     */
    public function getSummary()
    {
        try {
            $user = Auth::user();
            $cartItems = $user->getActiveCart();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'items_count' => $cartItems->count(),
                    'total_quantity' => $cartItems->sum('quantity'),
                    'total_pv' => $cartItems->sum('pv_bloccati'),
                    'pv_disponibili' => $user->pv_disponibili,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading cart summary: ' . $e->getMessage(),
            ], 500);
        }
    }
}
