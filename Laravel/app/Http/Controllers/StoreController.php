<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * StoreController
 * 
 * Handles e-commerce catalog browsing:
 * - List stores visible to user
 * - List categories with article counts
 * - List and filter articles
 * - Get single article details
 */
class StoreController extends Controller
{
    /**
     * Get all stores visible to the authenticated user.
     * 
     * GET /api/ecommerce/stores
     */
    public function getStores()
    {
        try {
            $user = Auth::user();

            $stores = Store::where('active', true)
                ->ordered()
                ->get()
                ->filter(function ($store) use ($user) {
                    return $store->isVisibleToUser($user);
                })
                ->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'store_name' => $store->store_name,
                        'slug' => $store->slug,
                        'store_type' => $store->store_type,
                        'description' => $store->description,
                        'logo_url' => $store->getLogoUrl(),
                        'banner_url' => $store->getBannerUrl(),
                        'articles_count' => $store->getAvailableArticlesCount(),
                    ];
                })
                ->values();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'stores' => $stores,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading stores: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get store details by slug.
     * 
     * GET /api/ecommerce/stores/{slug}
     */
    public function getStore(string $slug)
    {
        try {
            $user = Auth::user();
            $store = Store::where('slug', $slug)->first();

            if (!$store) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Store not found',
                ], 404);
            }

            if (!$store->isVisibleToUser($user)) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'You do not have access to this store',
                ], 403);
            }

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'store' => [
                        'id' => $store->id,
                        'store_name' => $store->store_name,
                        'slug' => $store->slug,
                        'store_type' => $store->store_type,
                        'description' => $store->description,
                        'logo_url' => $store->getLogoUrl(),
                        'banner_url' => $store->getBannerUrl(),
                        'contact_email' => $store->contact_email,
                        'articles_count' => $store->getAvailableArticlesCount(),
                        'featured_articles' => $store->getFeaturedArticles(4),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading store: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get categories, optionally filtered by store.
     * 
     * GET /api/ecommerce/categories
     * GET /api/ecommerce/categories?store_id=1
     */
    public function getCategories(Request $request)
    {
        try {
            $user = Auth::user();
            $storeId = $request->query('store_id');

            $query = Category::where('is_active', true)->ordered();

            // If store_id provided, only get categories that have articles in that store
            if ($storeId) {
                $query->whereHas('articles', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)->where('available', true);
                });
            }

            $categories = $query->get()
                ->filter(function ($category) use ($user) {
                    return $category->isVisibleToUser($user);
                })
                ->map(function ($category) use ($storeId) {
                    $articlesCount = $storeId
                        ? $category->articles()->where('store_id', $storeId)->where('available', true)->count()
                        : $category->getAllArticlesCount();

                    return [
                        'id' => $category->id,
                        'category_name' => $category->category_name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'icon' => $category->icon,
                        'parent_id' => $category->parent_id,
                        'articles_count' => $articlesCount,
                    ];
                })
                ->values();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'categories' => $categories,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get articles with optional filters.
     * 
     * GET /api/ecommerce/articles
     * 
     * Query params:
     * - store_id: Filter by store
     * - category_id: Filter by category
     * - featured: Only featured items (1/0)
     * - bestseller: Only bestseller items (1/0)
     * - min_pv: Minimum PV price
     * - max_pv: Maximum PV price
     * - search: Search in name/description
     * - sort: price_asc, price_desc, name_asc, name_desc, newest
     * - per_page: Items per page (default 12)
     */
    public function getArticles(Request $request)
    {
        try {
            $user = Auth::user();

            $query = Article::with(['category', 'store', 'thumbnail'])
                ->available();

            // Filter by store
            if ($request->filled('store_id')) {
                $store = Store::find($request->store_id);
                if (!$store || !$store->isVisibleToUser($user)) {
                    return response()->json([
                        'response' => 'error',
                        'status' => '403',
                        'message' => 'Store not accessible',
                    ], 403);
                }
                $query->byStore($request->store_id);
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $category = Category::find($request->category_id);
                if (!$category || !$category->isVisibleToUser($user)) {
                    return response()->json([
                        'response' => 'error',
                        'status' => '403',
                        'message' => 'Category not accessible',
                    ], 403);
                }
                // Include subcategories
                $categoryIds = array_merge([$category->id], $category->getDescendantIds());
                $query->whereIn('category_id', $categoryIds);
            }

            // Filter featured only
            if ($request->filled('featured') && $request->featured) {
                $query->featured();
            }

            // Filter bestseller only
            if ($request->filled('bestseller') && $request->bestseller) {
                $query->bestseller();
            }

            // Price range filter
            if ($request->filled('min_pv') && $request->filled('max_pv')) {
                $query->priceRange($request->min_pv, $request->max_pv);
            } elseif ($request->filled('min_pv')) {
                $query->where('pv_price', '>=', $request->min_pv);
            } elseif ($request->filled('max_pv')) {
                $query->where('pv_price', '<=', $request->max_pv);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('article_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sort = $request->get('sort', 'default');
            switch ($sort) {
                case 'price_asc':
                    $query->orderBy('pv_price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('pv_price', 'desc');
                    break;
                case 'name_asc':
                    $query->orderBy('article_name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('article_name', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->ordered(); // Use sort_order field
            }

            // Pagination
            $perPage = min($request->get('per_page', 12), 50);
            $articles = $query->paginate($perPage);

            // Filter results by user visibility and transform
            $items = collect($articles->items())
                ->filter(function ($article) use ($user) {
                    return $article->isVisibleToUser($user);
                })
                ->map(function ($article) {
                    return $this->transformArticle($article);
                })
                ->values();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'articles' => $items,
                    'pagination' => [
                        'current_page' => $articles->currentPage(),
                        'last_page' => $articles->lastPage(),
                        'per_page' => $articles->perPage(),
                        'total' => $articles->total(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading articles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single article details.
     * 
     * GET /api/ecommerce/articles/{id}
     */
    public function getArticle(int $id)
    {
        try {
            $user = Auth::user();

            $article = Article::with([
                'category',
                'store',
                'thumbnail',
                'assets',
                'stock',
                'attributeValues.attribute',
            ])->find($id);

            if (!$article) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Article not found',
                ], 404);
            }

            if (!$article->isVisibleToUser($user)) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'You do not have access to this article',
                ], 403);
            }

            // Get stock for user's default store or first available
            $stock = $article->stock->first();

            // Get related articles from same category
            $relatedArticles = Article::where('category_id', $article->category_id)
                ->where('id', '!=', $article->id)
                ->available()
                ->limit(4)
                ->get()
                ->filter(fn($a) => $a->isVisibleToUser($user))
                ->map(fn($a) => $this->transformArticle($a))
                ->values();

            // Build attributes array from EAV
            $attributes = $article->attributeValues->map(function ($av) {
                return [
                    'code' => $av->attribute->attribute_code ?? null,
                    'label' => $av->attribute->attribute_label ?? null,
                    'value' => $av->getValue(),
                ];
            })->filter(fn($a) => $a['code'] !== null);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'article' => [
                        'id' => $article->id,
                        'sku' => $article->sku,
                        'article_name' => $article->article_name,
                        'description' => $article->description,
                        'pv_price' => $article->pv_price,
                        'euro_price' => $article->euro_price,
                        'formatted_pv_price' => $article->formatted_pv_price,
                        'formatted_euro_price' => $article->formatted_euro_price,
                        'is_digital' => $article->is_digital,
                        'is_featured' => $article->is_featured,
                        'is_bestseller' => $article->is_bestseller,
                        'available' => $article->available,
                        'category' => $article->category ? [
                            'id' => $article->category->id,
                            'name' => $article->category->category_name,
                            'slug' => $article->category->slug,
                        ] : null,
                        'store' => $article->store ? [
                            'id' => $article->store->id,
                            'name' => $article->store->store_name,
                            'slug' => $article->store->slug,
                        ] : null,
                        'thumbnail_url' => $article->thumbnail?->getUrl(),
                        'gallery' => $article->assets->map(fn($a) => [
                            'id' => $a->id,
                            'url' => $a->getUrl(),
                            'type' => $a->asset_type,
                        ]),
                        'stock' => $stock ? [
                            'quantity' => $stock->quantity,
                            'in_stock' => !$stock->isOutOfStock(),
                            'low_stock' => $stock->isLowStock(),
                        ] : null,
                        'attributes' => $attributes,
                    ],
                    'related_articles' => $relatedArticles,
                    'user_pv' => [
                        'pv_disponibili' => $user->pv_disponibili,
                        'can_afford' => $user->hasEnoughPv($article->pv_price),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading article: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform article for list views.
     */
    private function transformArticle(Article $article): array
    {
        return [
            'id' => $article->id,
            'sku' => $article->sku,
            'article_name' => $article->article_name,
            'description' => \Str::limit($article->description, 150),
            'pv_price' => $article->pv_price,
            'euro_price' => $article->euro_price,
            'formatted_pv_price' => $article->formatted_pv_price,
            'is_digital' => $article->is_digital,
            'is_featured' => $article->is_featured,
            'is_bestseller' => $article->is_bestseller,
            'thumbnail_url' => $article->thumbnail?->getUrl(),
            'category_name' => $article->category?->category_name,
            'store_name' => $article->store?->store_name,
        ];
    }
}