<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('sort_order', 'asc');
    }

    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }

    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log category creation
        static::created(function ($category) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Category created", [
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'filter_id' => $category->filter_id,
                'is_active' => $category->is_active,
                'created_by' => $userName,
            ]);
        });

        // Log category updates
        static::updated(function ($category) {
            $changes = $category->getChanges();
            $original = $category->getOriginal();

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

                // Use warning if visibility (is_active) or hierarchy (parent_id) changed
                $importantFields = ['is_active', 'parent_id', 'filter_id'];
                $hasImportantChanges = !empty(array_intersect(array_keys($changesForLog), $importantFields));
                $level = $hasImportantChanges ? 'warning' : 'info';

                SystemLogService::ecommerce()->{$level}("Category updated", [
                    'category_id' => $category->id,
                    'category_name' => $category->category_name,
                    'changes' => $changesForLog,
                    'important_change' => $hasImportantChanges,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log category deletion
        static::deleted(function ($category) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Category deleted", [
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'slug' => $category->slug,
                'had_children' => $category->children()->count(),
                'had_articles' => $category->articles()->count(),
                'deleted_by' => $userName,
            ]);
        });
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

    public function isVisibleToUser($user)
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->filter_id) {
            return true;
        }

        return $this->filter->matchesUser($user);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function hasActiveChildren(): bool
    {
        return $this->activeChildren()->exists();
    }

    public function getDepth(): int
    {
        $depth = 0;
        $category = $this;

        while ($category->parent_id !== null) {
            $depth++;
            $category = $category->parent;

            if ($depth > 10) {
                break;
            }
        }

        return $depth;
    }

    public function getPath(): array
    {
        $path = [$this->category_name];
        $category = $this;

        while ($category->parent_id !== null) {
            $category = $category->parent;
            array_unshift($path, $category->category_name);

            if (count($path) > 10) {
                break;
            }
        }

        return $path;
    }

    public function getPathString(string $separator = ' > '): string
    {
        return implode($separator, $this->getPath());
    }

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

    public function getDescendantIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }

        return $ids;
    }

    public function getAllArticles()
    {
        $categoryIds = array_merge([$this->id], $this->getDescendantIds());

        return Article::whereIn('category_id', $categoryIds)
                      ->available()
                      ->ordered()
                      ->get();
    }

    public function getAllArticlesCount(): int
    {
        $categoryIds = array_merge([$this->id], $this->getDescendantIds());

        return Article::whereIn('category_id', $categoryIds)
                      ->where('available', true)
                      ->count();
    }

    public function moveTo(?int $newParentId): bool
    {
        if ($newParentId === $this->id) {
            return false;
        }

        if ($newParentId !== null && in_array($newParentId, $this->getDescendantIds())) {
            return false;
        }

        $oldParentId = $this->parent_id;
        $this->parent_id = $newParentId;
        $result = $this->save();

        if ($result) {
            SystemLogService::ecommerce()->info("Category moved", [
                'category_id' => $this->id,
                'category_name' => $this->category_name,
                'old_parent_id' => $oldParentId,
                'new_parent_id' => $newParentId,
            ]);
        }

        return $result;
    }

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

    public function getMetaTitleAttribute($value): string
    {
        return $value ?: $this->category_name;
    }

    public function getMetaDescriptionForSeo(): string
    {
        return $this->meta_description ?: ($this->description ?: '');
    }

    // ==================== STATIC METHODS ====================

    public static function getFlatTree(): array
    {
        $result = [];
        $roots = static::root()->active()->ordered()->get();

        foreach ($roots as $root) {
            static::addToFlatTree($result, $root, 0);
        }

        return $result;
    }

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

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}