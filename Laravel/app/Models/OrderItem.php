<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Item status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_FULFILLED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'order_id',
        'article_id',
        'article_name_snapshot',
        'article_sku_snapshot',
        'quantity',
        'pv_unit_price',
        'pv_total_price',
        'redemption_code',
        'item_status',
        'fulfilled_at',
        'fulfilled_by_user_id',
        'internal_note',
        'customer_note',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'pv_unit_price' => 'integer',
        'pv_total_price' => 'integer',
        'fulfilled_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * User who fulfilled this item (provided the redemption code).
     */
    public function fulfilledBy()
    {
        return $this->belongsTo(User::class, 'fulfilled_by_user_id');
    }

    // ==================== SCOPES ====================

    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByArticle($query, $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('item_status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('item_status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('item_status', self::STATUS_PROCESSING);
    }

    public function scopeFulfilled($query)
    {
        return $query->where('item_status', self::STATUS_FULFILLED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('item_status', self::STATUS_CANCELLED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('item_status', self::STATUS_REFUNDED);
    }

    public function scopeNeedsFulfillment($query)
    {
        return $query->whereIn('item_status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function scopeWithRedemptionCode($query)
    {
        return $query->whereNotNull('redemption_code');
    }

    public function scopeWithoutRedemptionCode($query)
    {
        return $query->whereNull('redemption_code')
                     ->whereIn('item_status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function scopeFulfilledBy($query, int $userId)
    {
        return $query->where('fulfilled_by_user_id', $userId);
    }

    public function scopeFulfilledBetween($query, $start, $end)
    {
        return $query->whereBetween('fulfilled_at', [$start, $end]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if item is pending.
     */
    public function isPending(): bool
    {
        return $this->item_status === self::STATUS_PENDING;
    }

    /**
     * Check if item is being processed.
     */
    public function isProcessing(): bool
    {
        return $this->item_status === self::STATUS_PROCESSING;
    }

    /**
     * Check if item is fulfilled.
     */
    public function isFulfilled(): bool
    {
        return $this->item_status === self::STATUS_FULFILLED;
    }

    /**
     * Check if item is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->item_status === self::STATUS_CANCELLED;
    }

    /**
     * Check if item is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->item_status === self::STATUS_REFUNDED;
    }

    /**
     * Check if item needs fulfillment.
     */
    public function needsFulfillment(): bool
    {
        return in_array($this->item_status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if item has a redemption code.
     */
    public function hasRedemptionCode(): bool
    {
        return !empty($this->redemption_code);
    }

    /**
     * Get status display label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'In attesa',
            self::STATUS_PROCESSING => 'In elaborazione',
            self::STATUS_FULFILLED => 'Evaso',
            self::STATUS_CANCELLED => 'Annullato',
            self::STATUS_REFUNDED => 'Rimborsato',
        ];

        return $labels[$this->item_status] ?? 'Sconosciuto';
    }

    /**
     * Get status CSS class for styling.
     */
    public function getStatusClass(): string
    {
        $classes = [
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PROCESSING => 'bg-blue-100 text-blue-800',
            self::STATUS_FULFILLED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            self::STATUS_REFUNDED => 'bg-purple-100 text-purple-800',
        ];

        return $classes[$this->item_status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get the article name (use snapshot if available, fallback to current).
     */
    public function getArticleName(): string
    {
        return $this->article_name_snapshot ?? ($this->article?->article_name ?? 'Articolo non disponibile');
    }

    /**
     * Get the article SKU (use snapshot if available, fallback to current).
     */
    public function getArticleSku(): string
    {
        return $this->article_sku_snapshot ?? ($this->article?->sku ?? '');
    }

    /**
     * Get formatted unit price.
     */
    public function getFormattedUnitPrice(): string
    {
        return number_format($this->pv_unit_price, 0, ',', '.') . ' PV';
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPrice(): string
    {
        return number_format($this->pv_total_price, 0, ',', '.') . ' PV';
    }

    // ==================== FULFILLMENT METHODS ====================

    /**
     * Start processing this item.
     */
    public function startProcessing(): self
    {
        $this->item_status = self::STATUS_PROCESSING;
        $this->save();

        return $this;
    }

    /**
     * Fulfill the item with a redemption code.
     */
    public function fulfill(string $redemptionCode, int $fulfilledByUserId, ?string $customerNote = null): self
    {
        $this->redemption_code = $redemptionCode;
        $this->item_status = self::STATUS_FULFILLED;
        $this->fulfilled_at = now();
        $this->fulfilled_by_user_id = $fulfilledByUserId;

        if ($customerNote) {
            $this->customer_note = $customerNote;
        }

        $this->save();

        // Check if all items in order are fulfilled, then mark order as processed
        $this->checkOrderCompletion();

        return $this;
    }

    /**
     * Cancel this item.
     */
    public function cancel(?string $reason = null): self
    {
        $this->item_status = self::STATUS_CANCELLED;
        
        if ($reason) {
            $this->internal_note = $this->internal_note
                ? $this->internal_note . "\nMotivo annullamento: " . $reason
                : "Motivo annullamento: " . $reason;
        }

        $this->save();

        return $this;
    }

    /**
     * Mark item as refunded.
     */
    public function refund(?string $reason = null): self
    {
        $this->item_status = self::STATUS_REFUNDED;
        
        if ($reason) {
            $this->internal_note = $this->internal_note
                ? $this->internal_note . "\nMotivo rimborso: " . $reason
                : "Motivo rimborso: " . $reason;
        }

        $this->save();

        return $this;
    }

    /**
     * Add internal note.
     */
    public function addInternalNote(string $note, ?int $userId = null): self
    {
        $timestamp = now()->format('d/m/Y H:i');
        $userName = $userId ? User::find($userId)?->name : 'Sistema';
        
        $formattedNote = "[{$timestamp}] {$userName}: {$note}";
        
        $this->internal_note = $this->internal_note
            ? $this->internal_note . "\n" . $formattedNote
            : $formattedNote;
        
        $this->save();

        return $this;
    }

    /**
     * Check if order should be marked as completed.
     */
    protected function checkOrderCompletion(): void
    {
        if (!$this->order) {
            return;
        }

        // Check if all items are fulfilled
        $allFulfilled = $this->order->orderItems()
                                    ->where('item_status', '!=', self::STATUS_FULFILLED)
                                    ->doesntExist();

        if ($allFulfilled && !$this->order->isCompleted()) {
            $this->order->markAsProcessed();
        }
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        static::creating(function ($item) {
            // Set default status
            if (empty($item->item_status)) {
                $item->item_status = self::STATUS_PENDING;
            }

            // Snapshot article data
            if ($item->article_id && empty($item->article_name_snapshot)) {
                $article = Article::find($item->article_id);
                if ($article) {
                    $item->article_name_snapshot = $article->article_name;
                    $item->article_sku_snapshot = $article->sku;
                }
            }

            // Calculate total price if not set
            if (empty($item->pv_total_price) && $item->pv_unit_price && $item->quantity) {
                $item->pv_total_price = $item->pv_unit_price * $item->quantity;
            }
        });
    }

    // ==================== STATIC METHODS ====================

    /**
     * Get fulfillment statistics for a date range.
     */
    public static function getFulfillmentStats($startDate, $endDate): array
    {
        $query = static::fulfilledBetween($startDate, $endDate);

        return [
            'total_fulfilled' => $query->count(),
            'total_pv' => $query->sum('pv_total_price'),
            'by_user' => (clone $query)
                ->selectRaw('fulfilled_by_user_id, COUNT(*) as count, SUM(pv_total_price) as total_pv')
                ->groupBy('fulfilled_by_user_id')
                ->with('fulfilledBy:id,name,cognome')
                ->get(),
        ];
    }

    /**
     * Get items needing fulfillment, ordered by order priority and creation date.
     */
    public static function getPendingFulfillments()
    {
        return static::needsFulfillment()
                     ->with(['order', 'article'])
                     ->join('orders', 'order_items.order_id', '=', 'orders.id')
                     ->orderByRaw("FIELD(orders.priority, 'urgent', 'high', 'normal', 'low')")
                     ->orderBy('orders.created_at', 'asc')
                     ->select('order_items.*')
                     ->get();
    }
}