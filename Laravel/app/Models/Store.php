<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    /**
     * The table has timestamps now (added via migration).
     */
    public $timestamps = true;

    protected $fillable = [
        'store_name',
        'slug',
        'store_type',
        'description',
        'logo_asset_id',
        'banner_path',
        'filter_id',
        'active',
        'sort_order',
        'contact_email',
    ];

    protected $casts = [
        'active' => 'boolean',
        'store_type' => 'string',
        'sort_order' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    public function filter()
    {
        return $this->belongsTo(Filter::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Available articles in this store.
     */
    public function availableArticles()
    {
        return $this->articles()->where('available', true);
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Store logo asset.
     */
    public function logo()
    {
        return $this->belongsTo(Asset::class, 'logo_asset_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeDigital($query)
    {
        return $query->where('store_type', 'digital');
    }

    public function scopePhysical($query)
    {
        return $query->where('store_type', 'physical');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('store_type', $type);
    }

    public function scopeWithArticleCount($query)
    {
        return $query->withCount(['articles' => function ($q) {
            $q->where('available', true);
        }]);
    }

    // ==================== HELPER METHODS ====================

    public function isDigital(): bool
    {
        return $this->store_type === 'digital';
    }

    public function isPhysical(): bool
    {
        return $this->store_type === 'physical';
    }

    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Check if store is visible to a specific user.
     */
    public function isVisibleToUser($user): bool
    {
        // If not active, not visible
        if (!$this->active) {
            return false;
        }

        // If no filter, visible to all
        if (!$this->filter_id) {
            return true;
        }

        return $this->filter->matchesUser($user);
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrl(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        return asset('storage/' . $this->logo->file_path);
    }

    /**
     * Get the banner URL.
     */
    public function getBannerUrl(): ?string
    {
        if (!$this->banner_path) {
            return null;
        }

        return asset('storage/' . $this->banner_path);
    }

    /**
     * Generate a unique slug from the store name.
     */
    public function generateSlug(): string
    {
        $slug = \Str::slug($this->store_name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get total count of available articles.
     */
    public function getAvailableArticlesCount(): int
    {
        return $this->availableArticles()->count();
    }

    /**
     * Get featured articles for this store.
     */
    public function getFeaturedArticles(int $limit = 4)
    {
        return $this->availableArticles()
                    ->where('is_featured', true)
                    ->ordered()
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get articles grouped by category.
     */
    public function getArticlesByCategory()
    {
        return $this->availableArticles()
                    ->with('category')
                    ->get()
                    ->groupBy('category_id');
    }

    /**
     * Check if store has low stock items.
     */
    public function hasLowStockItems(): bool
    {
        return $this->stock()->lowStock()->exists();
    }

    /**
     * Get low stock items for this store.
     */
    public function getLowStockItems()
    {
        return $this->stock()
                    ->lowStock()
                    ->with('article')
                    ->get();
    }

    /**
     * Get out of stock items for this store.
     */
    public function getOutOfStockItems()
    {
        return $this->stock()
                    ->where('quantity', '<=', 0)
                    ->with('article')
                    ->get();
    }

    // ==================== STATIC METHODS ====================

    /**
     * Find store by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get all active stores ordered.
     */
    public static function getAllActive()
    {
        return static::active()->ordered()->get();
    }

    /**
     * Get stores visible to a specific user.
     */
    public static function getVisibleToUser($user)
    {
        return static::active()
                     ->ordered()
                     ->get()
                     ->filter(function ($store) use ($user) {
                         return $store->isVisibleToUser($user);
                     });
    }
}