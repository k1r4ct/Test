<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsDatabaseOperations;

class Order extends Model
{
    use HasFactory, SoftDeletes, LogsDatabaseOperations;

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

    public function paymentMode()
    {
        return $this->paymentMethod();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

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

    public function isCompleted(): bool
    {
        return $this->orderStatus && $this->orderStatus->status_name === 'completato';
    }

    public function isCancelled(): bool
    {
        return $this->orderStatus && $this->orderStatus->status_name === 'annullato';
    }

    public function isPending(): bool
    {
        return $this->processed_at === null && !$this->isCancelled();
    }

    public function isInProcessing(): bool
    {
        return $this->processing_started_at !== null && $this->processed_at === null;
    }

    public function isAssigned(): bool
    {
        return $this->processed_by_user_id !== null;
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

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

    public function getFormattedTotalPv(): string
    {
        return number_format($this->total_pv, 0, ',', '.') . ' PV';
    }

    // ==================== PROCESSING METHODS ====================

    public function assignTo(int $userId): self
    {
        $this->processed_by_user_id = $userId;
        $this->save();

        return $this;
    }

    public function startProcessing(int $userId): self
    {
        $this->processed_by_user_id = $userId;
        $this->processing_started_at = now();
        $this->save();

        return $this;
    }

    public function markAsProcessed(?string $customerMessage = null): self
    {
        $this->processed_at = now();
        
        if ($customerMessage) {
            $this->customer_message = $customerMessage;
        }

        $completedStatus = OrderStatus::where('status_name', 'completato')->first();
        if ($completedStatus) {
            $this->order_status_id = $completedStatus->id;
        }

        $this->save();

        return $this;
    }

    public function cancel(string $reason): self
    {
        $this->cancellation_reason = $reason;
        $this->cancelled_at = now();

        $cancelledStatus = OrderStatus::where('status_name', 'annullato')->first();
        if ($cancelledStatus) {
            $this->order_status_id = $cancelledStatus->id;
        }

        $this->save();

        return $this;
    }

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

    public function areAllItemsFulfilled(): bool
    {
        return $this->orderItems()
                    ->where('item_status', '!=', 'fulfilled')
                    ->doesntExist();
    }

    public function getFulfilledItemsCount(): int
    {
        return $this->orderItems()->where('item_status', 'fulfilled')->count();
    }

    public function getPendingItemsCount(): int
    {
        return $this->orderItems()->where('item_status', 'pending')->count();
    }

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
        // ORDER CREATION
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }

            if (empty($order->priority)) {
                $order->priority = self::PRIORITY_NORMAL;
            }

            if ($order->user_id && empty($order->customer_email)) {
                $user = User::find($order->user_id);
                if ($user) {
                    $order->customer_email = $user->email;
                    $order->customer_name = trim($user->name . ' ' . ($user->cognome ?? ''));
                }
            }
        });

        // Log order creation
        static::created(function ($order) {
            $order->load('user');

            $userName = $order->user 
                ? $order->user->name . ' ' . $order->user->cognome 
                : 'User #' . $order->user_id;

            SystemLogService::ecommerce()->info("Order created", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'user_name' => $userName,
                'total_pv' => $order->total_pv,
                'priority' => $order->priority,
                'payment_method_id' => $order->payment_method_id,
            ]);
        });

        // ORDER UPDATES
        static::updated(function ($order) {
            $changes = $order->getChanges();
            $original = $order->getOriginal();

            // Build changes for log
            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if (!in_array($field, ['updated_at', 'admin_notes'])) {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];
                }
            }

            // Get status names
            $completedStatus = OrderStatus::where('status_name', 'completato')->first();
            $canceledStatus = OrderStatus::where('status_name', 'annullato')->first();
            
            // ORDER COMPLETED - Process PV deduction
            if ($completedStatus && $order->order_status_id == $completedStatus->id) {
                if ($order->isDirty('order_status_id')) {
                    
                    $user = User::find($order->user_id);
                    if ($user) {
                        $pvToDeduct = $order->total_pv;
                        $deductionDetails = [
                            'from_bonus' => 0,
                            'from_maturati' => 0,
                        ];
                        
                        if ($user->punti_bonus && $user->punti_bonus > 0) {
                            if ($user->punti_bonus >= $pvToDeduct) {
                                $user->decrement('punti_bonus', $pvToDeduct);
                                $deductionDetails['from_bonus'] = $pvToDeduct;
                            } else {
                                $remaining = $pvToDeduct - $user->punti_bonus;
                                $deductionDetails['from_bonus'] = $user->punti_bonus;
                                $deductionDetails['from_maturati'] = $remaining;
                                $user->punti_bonus = 0;
                                $user->decrement('punti_valore_maturati', $remaining);
                                $user->save();
                            }
                        } else {
                            $user->decrement('punti_valore_maturati', $pvToDeduct);
                            $deductionDetails['from_maturati'] = $pvToDeduct;
                        }
                        
                        $user->increment('punti_spesi', $order->total_pv);

                        // Log PV deduction
                        SystemLogService::ecommerce()->info("Order completed - PV deducted", [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'user_id' => $user->id,
                            'user_name' => $user->name . ' ' . $user->cognome,
                            'total_pv_deducted' => $pvToDeduct,
                            'deduction_details' => $deductionDetails,
                            'new_punti_bonus' => $user->punti_bonus,
                            'new_punti_valore_maturati' => $user->punti_valore_maturati,
                            'new_punti_spesi' => $user->punti_spesi,
                        ]);
                    }
                    
                    // Clean up completed cart items
                    $completedCartStatus = CartStatus::where('status_name', 'completato')->first();
                    if ($completedCartStatus) {
                        $deletedCount = CartItem::where('user_id', $order->user_id)
                                ->where('cart_status_id', $completedCartStatus->id)
                                ->delete();
                        
                        if ($deletedCount > 0) {
                            SystemLogService::ecommerce()->info("Cart items cleaned after order completion", [
                                'order_id' => $order->id,
                                'user_id' => $order->user_id,
                                'deleted_cart_items' => $deletedCount,
                            ]);
                        }
                    }
                }
            }
            
            // ORDER CANCELED - Log cancellation
            if ($canceledStatus && $order->order_status_id == $canceledStatus->id) {
                if ($order->isDirty('order_status_id')) {
                    SystemLogService::ecommerce()->warning("Order cancelled", [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_id' => $order->user_id,
                        'total_pv' => $order->total_pv,
                        'cancellation_reason' => $order->cancellation_reason,
                    ]);

                    $pendingCartStatus = CartStatus::where('status_name', 'in_attesa_di_pagamento')->first();
                    if ($pendingCartStatus) {
                        CartItem::where('user_id', $order->user_id)
                                ->where('cart_status_id', $pendingCartStatus->id)
                                ->delete();
                    }
                }
            }

            // Log general updates (if not status change already logged)
            if (!empty($changesForLog) && !$order->isDirty('order_status_id')) {
                $operatorName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                SystemLogService::ecommerce()->info("Order updated", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'changes' => $changesForLog,
                    'updated_by' => $operatorName,
                ]);
            }
        });

        // Log order deletion (soft delete)
        static::deleted(function ($order) {
            $operatorName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Order deleted", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'total_pv' => $order->total_pv,
                'was_completed' => $order->isCompleted(),
                'deleted_by' => $operatorName,
            ]);
        });
    }

    // ==================== STATIC METHODS ====================

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        $number = "{$prefix}-{$date}-{$random}";
        
        while (static::where('order_number', $number)->exists()) {
            $random = strtoupper(substr(uniqid(), -4));
            $number = "{$prefix}-{$date}-{$random}";
        }

        return $number;
    }

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