<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Order priority levels.
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    protected $fillable = [
        'order_number',
        'user_id',
        'total_pv',
        'order_status_id',
        'priority',
        'payment_method_id',
        'processed_by_user_id',
        'processing_started_at',
        'processed_at',
        'admin_notes',
        'customer_message',
        'customer_email',
        'customer_name',
        'cancellation_reason',
        'cancelled_at',
        'order_date',
    ];

    protected $casts = [
        'total_pv' => 'integer',
        'order_date' => 'datetime',
        'processing_started_at' => 'datetime',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(payment_mode::class, 'payment_method_id');
    }

    /**
     * Alias for backward compatibility.
     */
    public function paymentMode()
    {
        return $this->paymentMethod();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Backoffice user who processed this order.
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    // ==================== SCOPES ====================

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('order_status_id', $statusId);
    }

    public function scopeByStatusName($query, string $statusName)
    {
        return $query->whereHas('orderStatus', function ($q) use ($statusName) {
            $q->where('status_name', $statusName);
        });
    }

    public function scopeCompleted($query)
    {
        return $query->byStatusName('completato');
    }

    public function scopePending($query)
    {
        return $query->byStatusName('in_attesa');
    }

    public function scopeCancelled($query)
    {
        return $query->byStatusName('annullato');
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeProcessedBy($query, int $userId)
    {
        return $query->where('processed_by_user_id', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('processed_by_user_id');
    }

    public function scopeInProcessing($query)
    {
        return $query->whereNotNull('processing_started_at')
                     ->whereNull('processed_at');
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeNotProcessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Orders that need attention (pending, unassigned, or high priority).
     */
    public function scopeNeedingAttention($query)
    {
        return $query->whereNull('processed_at')
                     ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
                     ->orderBy('created_at', 'asc');
    }

    public function scopeCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->orderStatus && $this->orderStatus->status_name === 'completato';
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->orderStatus && $this->orderStatus->status_name === 'annullato';
    }

    /**
     * Check if order is pending (not yet processed).
     */
    public function isPending(): bool
    {
        return $this->processed_at === null && !$this->isCancelled();
    }

    /**
     * Check if order is currently being processed.
     */
    public function isInProcessing(): bool
    {
        return $this->processing_started_at !== null && $this->processed_at === null;
    }

    /**
     * Check if order is assigned to a backoffice user.
     */
    public function isAssigned(): bool
    {
        return $this->processed_by_user_id !== null;
    }

    /**
     * Check if order is urgent or high priority.
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Get priority display label.
     */
    public function getPriorityLabel(): string
    {
        $labels = [
            self::PRIORITY_LOW => 'Bassa',
            self::PRIORITY_NORMAL => 'Normale',
            self::PRIORITY_HIGH => 'Alta',
            self::PRIORITY_URGENT => 'Urgente',
        ];

        return $labels[$this->priority] ?? 'Normale';
    }

    /**
     * Get priority CSS class for styling.
     */
    public function getPriorityClass(): string
    {
        $classes = [
            self::PRIORITY_LOW => 'text-gray-500',
            self::PRIORITY_NORMAL => 'text-blue-500',
            self::PRIORITY_HIGH => 'text-orange-500',
            self::PRIORITY_URGENT => 'text-red-500',
        ];

        return $classes[$this->priority] ?? 'text-blue-500';
    }

    /**
     * Get formatted total PV.
     */
    public function getFormattedTotalPv(): string
    {
        return number_format($this->total_pv, 0, ',', '.') . ' PV';
    }

    // ==================== PROCESSING METHODS ====================

    /**
     * Assign order to a backoffice user.
     */
    public function assignTo(int $userId): self
    {
        $this->processed_by_user_id = $userId;
        $this->save();

        return $this;
    }

    /**
     * Start processing the order.
     */
    public function startProcessing(int $userId): self
    {
        $this->processed_by_user_id = $userId;
        $this->processing_started_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark order as processed/completed.
     */
    public function markAsProcessed(?string $customerMessage = null): self
    {
        $this->processed_at = now();
        
        if ($customerMessage) {
            $this->customer_message = $customerMessage;
        }

        // Update status to 'completato' if exists
        $completedStatus = OrderStatus::where('status_name', 'completato')->first();
        if ($completedStatus) {
            $this->order_status_id = $completedStatus->id;
        }

        $this->save();

        return $this;
    }

    /**
     * Cancel the order.
     */
    public function cancel(string $reason): self
    {
        $this->cancellation_reason = $reason;
        $this->cancelled_at = now();

        // Update status to 'annullato' if exists
        $cancelledStatus = OrderStatus::where('status_name', 'annullato')->first();
        if ($cancelledStatus) {
            $this->order_status_id = $cancelledStatus->id;
        }

        $this->save();

        return $this;
    }

    /**
     * Add internal note (append to existing).
     */
    public function addAdminNote(string $note, ?int $userId = null): self
    {
        $timestamp = now()->format('d/m/Y H:i');
        $userName = $userId ? User::find($userId)?->name : 'Sistema';
        
        $formattedNote = "[{$timestamp}] {$userName}: {$note}";
        
        $this->admin_notes = $this->admin_notes
            ? $this->admin_notes . "\n" . $formattedNote
            : $formattedNote;
        
        $this->save();

        return $this;
    }

    /**
     * Set customer info snapshot at order time.
     */
    public function snapshotCustomerInfo(): self
    {
        if ($this->user) {
            $this->customer_email = $this->user->email;
            $this->customer_name = trim($this->user->name . ' ' . ($this->user->cognome ?? ''));
            $this->save();
        }

        return $this;
    }

    // ==================== ORDER ITEMS METHODS ====================

    /**
     * Check if all items are fulfilled.
     */
    public function areAllItemsFulfilled(): bool
    {
        return $this->orderItems()
                    ->where('item_status', '!=', 'fulfilled')
                    ->doesntExist();
    }

    /**
     * Get count of fulfilled items.
     */
    public function getFulfilledItemsCount(): int
    {
        return $this->orderItems()->where('item_status', 'fulfilled')->count();
    }

    /**
     * Get count of pending items.
     */
    public function getPendingItemsCount(): int
    {
        return $this->orderItems()->where('item_status', 'pending')->count();
    }

    /**
     * Get fulfillment progress percentage.
     */
    public function getFulfillmentProgress(): float
    {
        $total = $this->orderItems()->count();
        if ($total === 0) {
            return 100;
        }

        $fulfilled = $this->getFulfilledItemsCount();
        return round(($fulfilled / $total) * 100, 1);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        static::creating(function ($order) {
            // Generate order number if not set
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }

            // Set default priority
            if (empty($order->priority)) {
                $order->priority = self::PRIORITY_NORMAL;
            }

            // Snapshot customer info
            if ($order->user_id && empty($order->customer_email)) {
                $user = User::find($order->user_id);
                if ($user) {
                    $order->customer_email = $user->email;
                    $order->customer_name = trim($user->name . ' ' . ($user->cognome ?? ''));
                }
            }
        });

        static::updated(function ($order) {
            // Get status names
            $completedStatus = OrderStatus::where('status_name', 'completato')->first();
            $canceledStatus = OrderStatus::where('status_name', 'annullato')->first();
            
            // ORDER COMPLETED
            if ($completedStatus && $order->order_status_id == $completedStatus->id) {
                if ($order->isDirty('order_status_id')) {
                    
                    $user = User::find($order->user_id);
                    if ($user) {
                        // Deduct PV from user's balance
                        $pvToDeduct = $order->total_pv;
                        
                        if ($user->punti_bonus && $user->punti_bonus > 0) {
                            if ($user->punti_bonus >= $pvToDeduct) {
                                $user->decrement('punti_bonus', $pvToDeduct);
                            } else {
                                $remaining = $pvToDeduct - $user->punti_bonus;
                                $user->punti_bonus = 0;
                                $user->decrement('punti_valore_maturati', $remaining);
                                $user->save();
                            }
                        } else {
                            $user->decrement('punti_valore_maturati', $pvToDeduct);
                        }
                        
                        // Update punti_spesi counter
                        $user->increment('punti_spesi', $order->total_pv);
                    }
                    
                    // Clean up completed cart items
                    $completedCartStatus = CartStatus::where('status_name', 'completato')->first();
                    if ($completedCartStatus) {
                        CartItem::where('user_id', $order->user_id)
                                ->where('cart_status_id', $completedCartStatus->id)
                                ->delete();
                    }
                }
            }
            
            // ORDER CANCELED - Release blocked PV
            if ($canceledStatus && $order->order_status_id == $canceledStatus->id) {
                if ($order->isDirty('order_status_id')) {
                    $pendingCartStatus = CartStatus::where('status_name', 'in_attesa_di_pagamento')->first();
                    if ($pendingCartStatus) {
                        CartItem::where('user_id', $order->user_id)
                                ->where('cart_status_id', $pendingCartStatus->id)
                                ->delete();
                    }
                }
            }
        });
    }

    // ==================== STATIC METHODS ====================

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        $number = "{$prefix}-{$date}-{$random}";
        
        // Ensure uniqueness
        while (static::where('order_number', $number)->exists()) {
            $random = strtoupper(substr(uniqid(), -4));
            $number = "{$prefix}-{$date}-{$random}";
        }

        return $number;
    }

    /**
     * Get orders statistics for a date range.
     */
    public static function getStatistics($startDate, $endDate): array
    {
        $query = static::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_orders' => $query->count(),
            'total_pv' => $query->sum('total_pv'),
            'completed' => (clone $query)->byStatusName('completato')->count(),
            'pending' => (clone $query)->whereNull('processed_at')->count(),
            'cancelled' => (clone $query)->byStatusName('annullato')->count(),
            'average_pv' => round($query->avg('total_pv') ?? 0),
        ];
    }
}