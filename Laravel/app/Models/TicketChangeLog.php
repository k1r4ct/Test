<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketChangeLog extends Model
{
    use HasFactory;

    protected $table = 'ticket_changes_log';

    /**
     * Status constants
     */
    public const STATUS_NEW = 'new';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_REMOVED = 'removed';  // Permanent deletion from DB

    /**
     * Category constants
     */
    public const CATEGORY_ORDINARY = 'ordinary';
    public const CATEGORY_EXTRAORDINARY = 'extraordinary';

    /**
     * Change type constants
     */
    public const CHANGE_TYPE_STATUS = 'status';
    public const CHANGE_TYPE_PRIORITY = 'priority';
    public const CHANGE_TYPE_CATEGORY = 'category';
    public const CHANGE_TYPE_BOTH = 'both';  // For status + priority (legacy)

    protected $fillable = [
        'ticket_id',
        'user_id',
        'previous_status',
        'new_status',
        'previous_priority',
        'new_priority',
        'previous_category',
        'new_category',
        'change_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeStatusChanges($query)
    {
        return $query->whereIn('change_type', [self::CHANGE_TYPE_STATUS, self::CHANGE_TYPE_BOTH]);
    }

    public function scopePriorityChanges($query)
    {
        return $query->whereIn('change_type', [self::CHANGE_TYPE_PRIORITY, self::CHANGE_TYPE_BOTH]);
    }

    /**
     * Scope for category changes only
     */
    public function scopeCategoryChanges($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_CATEGORY);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for auto-system changes (user_id = null)
     */
    public function scopeAutoChanges($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Get the previous category label in Italian
     */
    public function getPreviousCategoryLabelAttribute(): ?string
    {
        if (!$this->previous_category) {
            return null;
        }
        $labels = self::getCategoryLabels();
        return $labels[$this->previous_category] ?? $this->previous_category;
    }

    /**
     * Get the new category label in Italian
     */
    public function getNewCategoryLabelAttribute(): ?string
    {
        if (!$this->new_category) {
            return null;
        }
        $labels = self::getCategoryLabels();
        return $labels[$this->new_category] ?? $this->new_category;
    }

    /**
     * Get category labels mapping
     */
    public static function getCategoryLabels(): array
    {
        return [
            self::CATEGORY_ORDINARY      => 'Ordinario',
            self::CATEGORY_EXTRAORDINARY => 'Straordinario',
        ];
    }

    /**
     * Check if this log entry is a category change
     */
    public function isCategoryChange(): bool
    {
        return $this->change_type === self::CHANGE_TYPE_CATEGORY;
    }

    /**
     * Check if this log entry is a status change
     */
    public function isStatusChange(): bool
    {
        return in_array($this->change_type, [self::CHANGE_TYPE_STATUS, self::CHANGE_TYPE_BOTH]);
    }

    /**
     * Check if this log entry is a priority change
     */
    public function isPriorityChange(): bool
    {
        return in_array($this->change_type, [self::CHANGE_TYPE_PRIORITY, self::CHANGE_TYPE_BOTH]);
    }

    /**
     * Get a human-readable description of the change
     */
    public function getChangeDescriptionAttribute(): string
    {
        switch ($this->change_type) {
            case self::CHANGE_TYPE_STATUS:
                return "Stato cambiato da '{$this->previous_status}' a '{$this->new_status}'";
            
            case self::CHANGE_TYPE_PRIORITY:
                return "Priorità cambiata da '{$this->previous_priority}' a '{$this->new_priority}'";
            
            case self::CHANGE_TYPE_CATEGORY:
                $prevLabel = $this->previous_category_label ?? 'N/A';
                $newLabel = $this->new_category_label ?? 'N/A';
                return "Categoria cambiata da '{$prevLabel}' a '{$newLabel}'";
            
            case self::CHANGE_TYPE_BOTH:
                return "Stato e priorità modificati";
            
            default:
                return "Modifica effettuata";
        }
    }
}