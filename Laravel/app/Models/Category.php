<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'slug',
        'description',
        'filter_id',
        'parent_id',
        'icon',
        'image_path',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
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
     * Parent category (for hierarchical structure).
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Child categories (direct children only).
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('sort_order', 'asc');
    }

    /**
     * Active child categories.
     */
    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Recursive relationship to get all descendants.
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Recursive relationship to get all ancestors.
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildren($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeWithArticleCount($query)
    {
        return $query->withCount(['articles' => function ($q) {
            $q->where('available', true);
        }]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if category is visible to a specific user.
     */
    public function isVisibleToUser($user)
    {
        // If not active, not visible
        if (!$this->is_active) {
            return false;
        }

        // If no filter, visible to all
        if (!$this->filter_id) {
            return true;
        }

        return $this->filter->matchesUser($user);
    }

    /**
     * Check if this is a root category (no parent).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this category has active children.
     */
    public function hasActiveChildren(): bool
    {
        return $this->activeChildren()->exists();
    }

    /**
     * Get the depth level of this category in the hierarchy.
     */
    public function getDepth(): int
    {
        $depth = 0;
        $category = $this;

        while ($category->parent_id !== null) {
            $depth++;
            $category = $category->parent;

            // Safety check to prevent infinite loops
            if ($depth > 10) {
                break;
            }
        }

        return $depth;
    }

    /**
     * Get the full path of category names from root to this category.
     */
    public function getPath(): array
    {
        $path = [$this->category_name];
        $category = $this;

        while ($category->parent_id !== null) {
            $category = $category->parent;
            array_unshift($path, $category->category_name);

            // Safety check
            if (count($path) > 10) {
                break;
            }
        }

        return $path;
    }

    /**
     * Get the full path as a string.
     */
    public function getPathString(string $separator = ' > '): string
    {
        return implode($separator, $this->getPath());
    }

    /**
     * Get all ancestor IDs including self.
     */
    public function getAncestorIds(): array
    {
        $ids = [$this->id];
        $category = $this;

        while ($category->parent_id !== null) {
            $ids[] = $category->parent_id;
            $category = $category->parent;

            if (count($ids) > 10) {
                break;
            }
        }

        return $ids;
    }

    /**
     * Get all descendant IDs (children, grandchildren, etc.).
     */
    public function getDescendantIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }

        return $ids;
    }

    /**
     * Get all articles including those in descendant categories.
     */
    public function getAllArticles()
    {
        $categoryIds = array_merge([$this->id], $this->getDescendantIds());

        return Article::whereIn('category_id', $categoryIds)
                      ->available()
                      ->ordered()
                      ->get();
    }

    /**
     * Get count of all articles including descendants.
     */
    public function getAllArticlesCount(): int
    {
        $categoryIds = array_merge([$this->id], $this->getDescendantIds());

        return Article::whereIn('category_id', $categoryIds)
                      ->where('available', true)
                      ->count();
    }

    /**
     * Move category to a new parent.
     */
    public function moveTo(?int $newParentId): bool
    {
        // Prevent moving to self
        if ($newParentId === $this->id) {
            return false;
        }

        // Prevent moving to own descendant
        if ($newParentId !== null && in_array($newParentId, $this->getDescendantIds())) {
            return false;
        }

        $this->parent_id = $newParentId;
        return $this->save();
    }

    /**
     * Generate a unique slug from the category name.
     */
    public function generateSlug(): string
    {
        $slug = \Str::slug($this->category_name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get meta title (fallback to category name if not set).
     */
    public function getMetaTitleAttribute($value): string
    {
        return $value ?: $this->category_name;
    }

    /**
     * Get meta description (fallback to description if not set).
     */
    public function getMetaDescriptionForSeo(): string
    {
        return $this->meta_description ?: ($this->description ?: '');
    }

    // ==================== STATIC METHODS ====================

    /**
     * Get all categories as a flat tree (for dropdowns).
     */
    public static function getFlatTree(): array
    {
        $result = [];
        $roots = static::root()->active()->ordered()->get();

        foreach ($roots as $root) {
            static::addToFlatTree($result, $root, 0);
        }

        return $result;
    }

    /**
     * Helper to build flat tree recursively.
     */
    protected static function addToFlatTree(array &$result, Category $category, int $depth): void
    {
        $result[] = [
            'id' => $category->id,
            'name' => $category->category_name,
            'depth' => $depth,
            'display_name' => str_repeat('— ', $depth) . $category->category_name,
        ];

        foreach ($category->activeChildren()->ordered()->get() as $child) {
            static::addToFlatTree($result, $child, $depth + 1);
        }
    }

    /**
     * Get categories as nested tree structure.
     */
    public static function getNestedTree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::root()
                     ->active()
                     ->ordered()
                     ->with(['activeChildren' => function ($q) {
                         $q->ordered()->with('activeChildren');
                     }])
                     ->get();
    }

    /**
     * Find category by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}