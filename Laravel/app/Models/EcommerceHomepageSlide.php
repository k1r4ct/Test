<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use App\Traits\LogsDatabaseOperations;
use Illuminate\Support\Facades\Auth;

class EcommerceHomepageSlide extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $table = 'ecommerce_homepage_slides';

    protected $fillable = [
        'title',
        'description',
        'badge_text',
        'badge_icon',
        'cta_text',
        'cta_action',
        'cta_url',
        'cta_disabled',
        'image_asset_id',
        'image_url',
        'gradient',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'cta_disabled' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Valid CTA action types.
     */
    public const ACTION_SCROLL_CATALOG = 'scroll-catalog';
    public const ACTION_OPEN_WALLET = 'open-wallet';
    public const ACTION_LINK = 'link';
    public const ACTION_COMING_SOON = 'coming-soon';

    public const VALID_ACTIONS = [
        self::ACTION_SCROLL_CATALOG,
        self::ACTION_OPEN_WALLET,
        self::ACTION_LINK,
        self::ACTION_COMING_SOON,
    ];

    // ==================== RELATIONSHIPS ====================

    public function imageAsset()
    {
        return $this->belongsTo(Asset::class, 'image_asset_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    // ==================== SCOPES ====================

    /**
     * Only active slides, respecting scheduling dates.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get the image URL: prefer asset, fallback to external URL.
     */
    public function getImageUrl(): ?string
    {
        if ($this->imageAsset) {
            return $this->imageAsset->getUrl();
        }

        return $this->image_url;
    }

    /**
     * Check if the slide is currently visible (active + within schedule).
     */
    public function isCurrentlyVisible(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Transform to array for consumer API response.
     */
    public function toConsumerArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'badge_text' => $this->badge_text,
            'badge_icon' => $this->badge_icon,
            'cta_text' => $this->cta_text,
            'cta_action' => $this->cta_action,
            'cta_url' => $this->cta_url,
            'cta_disabled' => $this->cta_disabled,
            'image_url' => $this->getImageUrl(),
            'gradient' => $this->gradient,
        ];
    }

    /**
     * Transform to array for admin API response (includes all fields).
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'badge_text' => $this->badge_text,
            'badge_icon' => $this->badge_icon,
            'cta_text' => $this->cta_text,
            'cta_action' => $this->cta_action,
            'cta_url' => $this->cta_url,
            'cta_disabled' => $this->cta_disabled,
            'image_asset_id' => $this->image_asset_id,
            'image_url' => $this->image_url,
            'image_resolved_url' => $this->getImageUrl(),
            'gradient' => $this->gradient,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $this->ends_at?->format('Y-m-d H:i:s'),
            'is_currently_visible' => $this->isCurrentlyVisible(),
            'created_by' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name . ' ' . $this->createdBy->cognome,
            ] : null,
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name . ' ' . $this->updatedBy->cognome,
            ] : null,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        static::created(function ($slide) {
            $userName = Auth::check()
                ? Auth::user()->name . ' ' . Auth::user()->cognome
                : 'Sistema';

            SystemLogService::ecommerce()->info("Ecommerce homepage slide created", [
                'slide_id' => $slide->id,
                'title' => $slide->title,
                'cta_action' => $slide->cta_action,
                'sort_order' => $slide->sort_order,
                'is_active' => $slide->is_active,
                'created_by' => $userName,
            ]);
        });

        static::updated(function ($slide) {
            $changes = $slide->getChanges();
            $original = $slide->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                $userName = Auth::check()
                    ? Auth::user()->name . ' ' . Auth::user()->cognome
                    : 'Sistema';

                SystemLogService::ecommerce()->info("Ecommerce homepage slide updated", [
                    'slide_id' => $slide->id,
                    'title' => $slide->title,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        static::deleted(function ($slide) {
            $userName = Auth::check()
                ? Auth::user()->name . ' ' . Auth::user()->cognome
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Ecommerce homepage slide deleted", [
                'slide_id' => $slide->id,
                'title' => $slide->title,
                'deleted_by' => $userName,
            ]);
        });
    }
}