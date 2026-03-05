<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use App\Traits\LogsDatabaseOperations;
use Illuminate\Support\Facades\Auth;

class EcommerceHomepageProductRow extends Model
{
    use HasFactory, LogsDatabaseOperations;

    protected $table = 'ecommerce_homepage_product_rows';

    protected $fillable = [
        'row_key',
        'title',
        'icon',
        'row_type',
        'display_style',
        'items_per_row',
        'max_items',
        'category_id',
        'store_id',
        'is_sponsored',
        'sponsor_label',
        'filter_id',
        'sort_order',
        'is_active',
        'is_system',
        'created_by_user_id',
    ];

    protected $casts = [
        'items_per_row' => 'integer',
        'max_items' => 'integer',
        'is_sponsored' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Row type constants.
     */
    public const TYPE_FEATURED = 'featured';
    public const TYPE_NEW_ARRIVALS = 'new_arrivals';
    public const TYPE_BESTSELLERS = 'bestsellers';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_STORE_SHOWCASE = 'store_showcase';
    public const TYPE_MANUAL = 'manual';

    public const VALID_TYPES = [
        self::TYPE_FEATURED,
        self::TYPE_NEW_ARRIVALS,
        self::TYPE_BESTSELLERS,
        self::TYPE_CATEGORY,
        self::TYPE_STORE_SHOWCASE,
        self::TYPE_MANUAL,
    ];

    public const DISPLAY_GRID = 'grid';
    public const DISPLAY_CAROUSEL = 'carousel';
    public const DISPLAY_COMPACT = 'compact';

    // ==================== RELATIONSHIPS ====================

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function filter()
    {
        return $this->belongsTo(Filter::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Manual/override article selections for this row.
     */
    public function rowArticles()
    {
        return $this->hasMany(EcommerceHomepageRowArticle::class, 'ecommerce_homepage_product_row_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order', 'asc');
    }

    /**
     * All row articles including inactive (for admin).
     */
    public function allRowArticles()
    {
        return $this->hasMany(EcommerceHomepageRowArticle::class, 'ecommerce_homepage_product_row_id')
                    ->orderBy('sort_order', 'asc');
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

    public function scopeByType($query, string $type)
    {
        return $query->where('row_type', $type);
    }

    public function scopeSponsored($query)
    {
        return $query->where('is_sponsored', true);
    }

    // ==================== ARTICLE RESOLUTION ====================

    /**
     * Resolve articles for this row based on row_type.
     * 
     * Priority:
     * 1. If row has manual rowArticles entries → use those (override)
     * 2. Otherwise → resolve automatically based on row_type
     */
    public function resolveArticles($user = null)
    {
        // Check for manual override first
        $manualArticles = $this->rowArticles()
            ->with(['article.thumbnail', 'article.category', 'article.store', 'customThumbnail'])
            ->get();

        if ($manualArticles->isNotEmpty()) {
            return $this->buildManualArticleList($manualArticles, $user);
        }

        // Automatic resolution based on row_type
        return $this->resolveAutomaticArticles($user);
    }

    /**
     * Build article list from manual selections with custom thumbnails.
     */
    private function buildManualArticleList($manualArticles, $user = null)
    {
        return $manualArticles
            ->filter(function ($rowArticle) use ($user) {
                if (!$rowArticle->article || !$rowArticle->article->available) {
                    return false;
                }
                if ($user && !$rowArticle->article->isVisibleToUser($user)) {
                    return false;
                }
                return true;
            })
            ->map(function ($rowArticle) {
                return $this->transformArticle(
                    $rowArticle->article,
                    $rowArticle->custom_title,
                    $rowArticle->getResolvedThumbnailUrl()
                );
            })
            ->values()
            ->take($this->max_items);
    }

    /**
     * Resolve articles automatically based on row_type.
     */
    private function resolveAutomaticArticles($user = null)
    {
        $query = Article::with(['thumbnail', 'category', 'store'])
            ->available();

        switch ($this->row_type) {
            case self::TYPE_FEATURED:
                $query->featured()->ordered();
                break;

            case self::TYPE_NEW_ARRIVALS:
                $query->orderBy('id', 'desc');
                break;

            case self::TYPE_BESTSELLERS:
                $query->where('is_bestseller', true)->ordered();
                break;

            case self::TYPE_CATEGORY:
                if ($this->category_id) {
                    $query->byCategory($this->category_id)->ordered();
                }
                break;

            case self::TYPE_STORE_SHOWCASE:
                if ($this->store_id) {
                    $query->byStore($this->store_id)->ordered();
                }
                break;

            case self::TYPE_MANUAL:
                return collect();
        }

        $articles = $query->limit($this->max_items)->get();

        if ($user) {
            $articles = $articles->filter(fn($a) => $a->isVisibleToUser($user));
        }

        return $articles->map(fn($article) => $this->transformArticle($article))
                        ->values();
    }

    /**
     * Transform an article for the consumer API response.
     */
    private function transformArticle(Article $article, ?string $customTitle = null, ?string $customThumbnailUrl = null): array
    {
        return [
            'id' => $article->id,
            'sku' => $article->sku,
            'article_name' => $customTitle ?? $article->article_name,
            'description' => \Str::limit($article->description, 150),
            'pv_price' => $article->pv_price,
            'euro_price' => $article->euro_price,
            'formatted_pv_price' => $article->formatted_pv_price,
            'is_digital' => $article->is_digital,
            'is_featured' => $article->is_featured,
            'is_bestseller' => $article->is_bestseller,
            'thumbnail_url' => $customThumbnailUrl ?? $article->thumbnail?->getUrl(),
            'category_name' => $article->category?->category_name,
            'store_name' => $article->store?->store_name,
        ];
    }

    /**
     * Transform row for consumer API response (with resolved articles).
     */
    public function toConsumerArray($user = null): array
    {
        $data = [
            'id' => $this->id,
            'row_key' => $this->row_key,
            'title' => $this->title,
            'icon' => $this->icon,
            'row_type' => $this->row_type,
            'display_style' => $this->display_style,
            'items_per_row' => $this->items_per_row,
            'max_items' => $this->max_items,
            'is_sponsored' => $this->is_sponsored,
            'sponsor_label' => $this->sponsor_label,
            'articles' => $this->resolveArticles($user)->toArray(),
        ];

        if ($this->row_type === self::TYPE_STORE_SHOWCASE && $this->store) {
            $data['store'] = [
                'id' => $this->store->id,
                'store_name' => $this->store->store_name,
                'logo_url' => $this->store->getLogoUrl(),
            ];
        }

        if ($this->row_type === self::TYPE_CATEGORY && $this->category) {
            $data['category_id'] = $this->category->id;
            $data['category_name'] = $this->category->category_name;
            $data['category_slug'] = $this->category->slug;
        }

        return $data;
    }

    /**
     * Transform row for admin API response (includes config details).
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'row_key' => $this->row_key,
            'title' => $this->title,
            'icon' => $this->icon,
            'row_type' => $this->row_type,
            'display_style' => $this->display_style,
            'items_per_row' => $this->items_per_row,
            'max_items' => $this->max_items,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->category_name,
            'store_id' => $this->store_id,
            'store_name' => $this->store?->store_name,
            'is_sponsored' => $this->is_sponsored,
            'sponsor_label' => $this->sponsor_label,
            'filter_id' => $this->filter_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'row_articles_count' => $this->allRowArticles()->count(),
            'created_by' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name . ' ' . $this->createdBy->cognome,
            ] : null,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * Check if this row is visible to a specific user based on filter.
     */
    public function isVisibleToUser($user): bool
    {
        if (!$this->filter) {
            return true;
        }

        return $this->filter->matchesUser($user);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        static::created(function ($row) {
            $userName = Auth::check()
                ? Auth::user()->name . ' ' . Auth::user()->cognome
                : 'Sistema';

            SystemLogService::ecommerce()->info("Ecommerce homepage product row created", [
                'row_id' => $row->id,
                'row_key' => $row->row_key,
                'title' => $row->title,
                'row_type' => $row->row_type,
                'is_sponsored' => $row->is_sponsored,
                'created_by' => $userName,
            ]);
        });

        static::updated(function ($row) {
            $changes = $row->getChanges();
            $original = $row->getOriginal();

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

                SystemLogService::ecommerce()->info("Ecommerce homepage product row updated", [
                    'row_id' => $row->id,
                    'row_key' => $row->row_key,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        static::deleting(function ($row) {
            if ($row->is_system) {
                throw new \Exception("Cannot delete system row: {$row->row_key}");
            }
        });

        static::deleted(function ($row) {
            $userName = Auth::check()
                ? Auth::user()->name . ' ' . Auth::user()->cognome
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Ecommerce homepage product row deleted", [
                'row_id' => $row->id,
                'row_key' => $row->row_key,
                'title' => $row->title,
                'deleted_by' => $userName,
            ]);
        });
    }
}