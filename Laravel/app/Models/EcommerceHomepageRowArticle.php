<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use App\Traits\LogsDatabaseOperations;
use Illuminate\Support\Facades\Auth;

class EcommerceHomepageRowArticle extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $table = 'ecommerce_homepage_row_articles';

    protected $fillable = [
        'ecommerce_homepage_product_row_id',
        'article_id',
        'custom_thumbnail_asset_id',
        'apply_thumbnail_globally',
        'custom_title',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'apply_thumbnail_globally' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function productRow()
    {
        return $this->belongsTo(EcommerceHomepageProductRow::class, 'ecommerce_homepage_product_row_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function customThumbnail()
    {
        return $this->belongsTo(Asset::class, 'custom_thumbnail_asset_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get the resolved thumbnail URL.
     * Priority: custom thumbnail → article thumbnail → null
     */
    public function getResolvedThumbnailUrl(): ?string
    {
        if ($this->customThumbnail) {
            return $this->customThumbnail->getUrl();
        }

        return $this->article?->thumbnail?->getUrl();
    }

    /**
     * If apply_thumbnail_globally is true, update the article's thumbnail.
     */
    public function applyGlobalThumbnailIfNeeded(): void
    {
        if (!$this->apply_thumbnail_globally || !$this->custom_thumbnail_asset_id) {
            return;
        }

        $article = $this->article;
        if ($article && $article->thumbnail_asset_id !== $this->custom_thumbnail_asset_id) {
            $article->update([
                'thumbnail_asset_id' => $this->custom_thumbnail_asset_id,
            ]);

            SystemLogService::ecommerce()->info("Article thumbnail updated globally from ecommerce homepage row", [
                'article_id' => $article->id,
                'article_name' => $article->article_name,
                'new_thumbnail_asset_id' => $this->custom_thumbnail_asset_id,
                'ecommerce_homepage_product_row_id' => $this->ecommerce_homepage_product_row_id,
            ]);
        }
    }

    /**
     * Transform for admin API response.
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'article_name' => $this->article?->article_name,
            'article_sku' => $this->article?->sku,
            'article_pv_price' => $this->article?->pv_price,
            'article_thumbnail_url' => $this->article?->thumbnail?->getUrl(),
            'custom_thumbnail_asset_id' => $this->custom_thumbnail_asset_id,
            'custom_thumbnail_url' => $this->customThumbnail?->getUrl(),
            'resolved_thumbnail_url' => $this->getResolvedThumbnailUrl(),
            'apply_thumbnail_globally' => $this->apply_thumbnail_globally,
            'custom_title' => $this->custom_title,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        static::created(function ($rowArticle) {
            $rowArticle->applyGlobalThumbnailIfNeeded();

            $userName = Auth::check()
                ? Auth::user()->name . ' ' . Auth::user()->cognome
                : 'Sistema';

            SystemLogService::ecommerce()->info("Article added to ecommerce homepage row", [
                'row_article_id' => $rowArticle->id,
                'ecommerce_homepage_product_row_id' => $rowArticle->ecommerce_homepage_product_row_id,
                'article_id' => $rowArticle->article_id,
                'has_custom_thumbnail' => !is_null($rowArticle->custom_thumbnail_asset_id),
                'apply_thumbnail_globally' => $rowArticle->apply_thumbnail_globally,
                'created_by' => $userName,
            ]);
        });

        static::updated(function ($rowArticle) {
            $rowArticle->applyGlobalThumbnailIfNeeded();

            $changes = $rowArticle->getChanges();
            $original = $rowArticle->getOriginal();

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
                SystemLogService::ecommerce()->info("Ecommerce homepage row article updated", [
                    'row_article_id' => $rowArticle->id,
                    'article_id' => $rowArticle->article_id,
                    'changes' => $changesForLog,
                ]);
            }
        });

        static::deleted(function ($rowArticle) {
            SystemLogService::ecommerce()->info("Article removed from ecommerce homepage row", [
                'row_article_id' => $rowArticle->id,
                'ecommerce_homepage_product_row_id' => $rowArticle->ecommerce_homepage_product_row_id,
                'article_id' => $rowArticle->article_id,
            ]);
        });
    }
}