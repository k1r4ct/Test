<?php

namespace App\Jobs;

use App\Models\CartItem;
use App\Models\CartStatus;
use App\Services\SystemLogService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * CartCleanupJob
 * 
 * Automatically removes expired cart items to release blocked PV.
 * 
 * Business Rules:
 * - Cart items expire after a configurable timeout (default: 30 minutes)
 * - Expired items are deleted, releasing the blocked PV
 * - This job should run every 5-10 minutes via scheduler
 * 
 * Run manually: php artisan queue:work
 * Or via scheduler in Kernel.php
 */
class CartCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cart expiration time in minutes
     */
    protected int $expirationMinutes;

    /**
     * Create a new job instance.
     */
    public function __construct(int $expirationMinutes = 30)
    {
        $this->expirationMinutes = $expirationMinutes;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            // Get "attivo" status ID
            $activeStatus = CartStatus::where('status_name', 'attivo')->first();
            
            if (!$activeStatus) {
                SystemLogService::ecommerce()->warning("CartCleanupJob: Active cart status not found");
                return;
            }

            // Calculate expiration threshold
            $expirationThreshold = Carbon::now()->subMinutes($this->expirationMinutes);

            // Find expired cart items with relationships for logging
            $expiredItems = CartItem::with(['user', 'article'])
                ->where('cart_status_id', $activeStatus->id)
                ->where('updated_at', '<', $expirationThreshold)
                ->get();

            if ($expiredItems->isEmpty()) {
                // No expired items, job completed quickly
                return;
            }

            $totalItemsRemoved = 0;
            $totalPvReleased = 0;
            $affectedUsers = [];

            DB::beginTransaction();

            try {
                foreach ($expiredItems as $item) {
                    // Track statistics
                    $totalPvReleased += $item->pv_bloccati;
                    $totalItemsRemoved++;
                    
                    // Track affected users
                    if (!isset($affectedUsers[$item->user_id])) {
                        $affectedUsers[$item->user_id] = [
                            'user_name' => $item->user 
                                ? $item->user->name . ' ' . $item->user->cognome 
                                : 'User #' . $item->user_id,
                            'items_removed' => 0,
                            'pv_released' => 0,
                        ];
                    }
                    $affectedUsers[$item->user_id]['items_removed']++;
                    $affectedUsers[$item->user_id]['pv_released'] += $item->pv_bloccati;

                    // Log individual item removal
                    SystemLogService::ecommerce()->info("Cart item expired and removed", [
                        'cart_item_id' => $item->id,
                        'user_id' => $item->user_id,
                        'user_name' => $affectedUsers[$item->user_id]['user_name'],
                        'article_id' => $item->article_id,
                        'article_name' => $item->article ? $item->article->article_name : null,
                        'quantity' => $item->quantity,
                        'pv_released' => $item->pv_bloccati,
                        'cart_age_minutes' => $item->updated_at->diffInMinutes(now()),
                        'expiration_threshold_minutes' => $this->expirationMinutes,
                    ]);

                    // Delete the item (this triggers CartItem's deleted event for additional logging)
                    $item->delete();
                }

                DB::commit();

                // Log job summary
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                SystemLogService::ecommerce()->info("CartCleanupJob completed", [
                    'total_items_removed' => $totalItemsRemoved,
                    'total_pv_released' => $totalPvReleased,
                    'affected_users_count' => count($affectedUsers),
                    'affected_users' => array_map(function ($userId, $data) {
                        return [
                            'user_id' => $userId,
                            'user_name' => $data['user_name'],
                            'items' => $data['items_removed'],
                            'pv' => $data['pv_released'],
                        ];
                    }, array_keys($affectedUsers), array_values($affectedUsers)),
                    'expiration_threshold_minutes' => $this->expirationMinutes,
                    'execution_time_ms' => $executionTime,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            SystemLogService::ecommerce()->error("CartCleanupJob failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        SystemLogService::ecommerce()->error("CartCleanupJob failed permanently", [
            'error' => $exception->getMessage(),
        ], $exception);
    }
}
