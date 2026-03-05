<?php

namespace App\Http\Controllers;

use App\Models\EcommerceHomepageSlide;
use App\Models\EcommerceHomepageProductRow;
use App\Models\EcommerceHomepageRowArticle;
use App\Models\Article;
use App\Models\Asset;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * EcommerceHomepageAdminController
 * 
 * Admin/BO endpoints for managing the e-commerce homepage:
 * - Hero carousel slides (CRUD + reorder)
 * - Product rows (CRUD + reorder)
 * - Row articles (add/remove/reorder articles per row, custom thumbnails)
 * 
 * Access: role_id 1 (Admin) and 5 (BackOffice)
 */
class EcommerceHomepageAdminController extends Controller
{
    private const ADMIN_ROLES = [1, 5];

    private function checkPermission()
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role_id, self::ADMIN_ROLES)) {
            return response()->json([
                'response' => 'error',
                'status' => '403',
                'message' => 'Access denied',
            ], 403);
        }
        return null;
    }

    // ==================================================================================
    //  SLIDES
    // ==================================================================================

    /**
     * GET /api/ecommerce/admin/homepage/slides
     */
    public function getSlides()
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $slides = EcommerceHomepageSlide::with(['imageAsset', 'createdBy', 'updatedBy'])
                ->ordered()
                ->get()
                ->map(fn($s) => $s->toAdminArray());

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['slides' => $slides],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading slides: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/ecommerce/admin/homepage/slides
     */
    public function createSlide(Request $request)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'badge_text' => 'nullable|string|max:100',
                'badge_icon' => 'nullable|string|max:50',
                'cta_text' => 'nullable|string|max:100',
                'cta_action' => 'nullable|string|in:' . implode(',', EcommerceHomepageSlide::VALID_ACTIONS),
                'cta_url' => 'nullable|string|max:500',
                'cta_disabled' => 'nullable|boolean',
                'image_asset_id' => 'nullable|exists:assets,id',
                'image_url' => 'nullable|string|max:500',
                'gradient' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $data = $validator->validated();
            $data['created_by_user_id'] = Auth::id();

            if (!isset($data['sort_order'])) {
                $data['sort_order'] = (EcommerceHomepageSlide::max('sort_order') ?? 0) + 1;
            }

            $slide = EcommerceHomepageSlide::create($data);
            $slide->load(['imageAsset', 'createdBy']);

            return response()->json([
                'response' => 'ok',
                'status' => '201',
                'body' => [
                    'message' => 'Slide created',
                    'slide' => $slide->toAdminArray(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error creating slide: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ecommerce/admin/homepage/slides/{id}
     */
    public function getSlide(int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $slide = EcommerceHomepageSlide::with(['imageAsset', 'createdBy', 'updatedBy'])->find($id);

            if (!$slide) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Slide not found',
                ], 404);
            }

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['slide' => $slide->toAdminArray()],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading slide: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/ecommerce/admin/homepage/slides/{id}
     */
    public function updateSlide(Request $request, int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $slide = EcommerceHomepageSlide::find($id);

            if (!$slide) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Slide not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:2000',
                'badge_text' => 'nullable|string|max:100',
                'badge_icon' => 'nullable|string|max:50',
                'cta_text' => 'nullable|string|max:100',
                'cta_action' => 'nullable|string|in:' . implode(',', EcommerceHomepageSlide::VALID_ACTIONS),
                'cta_url' => 'nullable|string|max:500',
                'cta_disabled' => 'nullable|boolean',
                'image_asset_id' => 'nullable|exists:assets,id',
                'image_url' => 'nullable|string|max:500',
                'gradient' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $data = $validator->validated();
            $data['updated_by_user_id'] = Auth::id();

            $slide->update($data);
            $slide->load(['imageAsset', 'createdBy', 'updatedBy']);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Slide updated',
                    'slide' => $slide->toAdminArray(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error updating slide: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/ecommerce/admin/homepage/slides/{id}
     */
    public function deleteSlide(int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $slide = EcommerceHomepageSlide::find($id);

            if (!$slide) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Slide not found',
                ], 404);
            }

            $slide->delete();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Slide deleted'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error deleting slide: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/ecommerce/admin/homepage/slides-reorder
     */
    public function reorderSlides(Request $request)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $validator = Validator::make($request->all(), [
                'order' => 'required|array|min:1',
                'order.*' => 'integer|exists:ecommerce_homepage_slides,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            DB::transaction(function () use ($request) {
                foreach ($request->order as $index => $slideId) {
                    EcommerceHomepageSlide::where('id', $slideId)
                        ->update(['sort_order' => $index]);
                }
            });

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Slides reordered'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error reordering slides: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================================================================================
    //  PRODUCT ROWS
    // ==================================================================================

    /**
     * GET /api/ecommerce/admin/homepage/rows
     */
    public function getRows()
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $rows = EcommerceHomepageProductRow::with(['category', 'store', 'filter', 'createdBy'])
                ->ordered()
                ->get()
                ->map(fn($r) => $r->toAdminArray());

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['rows' => $rows],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading rows: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/ecommerce/admin/homepage/rows
     */
    public function createRow(Request $request)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'row_key' => 'nullable|string|max:100|unique:ecommerce_homepage_product_rows,row_key',
                'icon' => 'nullable|string|max:50',
                'row_type' => 'required|string|in:' . implode(',', EcommerceHomepageProductRow::VALID_TYPES),
                'display_style' => 'nullable|string|in:grid,carousel,compact',
                'items_per_row' => 'nullable|integer|min:1|max:6',
                'max_items' => 'nullable|integer|min:1|max:24',
                'category_id' => 'nullable|exists:categories,id',
                'store_id' => 'nullable|exists:stores,id',
                'is_sponsored' => 'nullable|boolean',
                'sponsor_label' => 'nullable|string|max:100',
                'filter_id' => 'nullable|exists:filters,id',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $data = $validator->validated();
            $data['created_by_user_id'] = Auth::id();

            if (empty($data['row_key'])) {
                $data['row_key'] = Str::slug($data['title']) . '_' . time();
            }

            if (!isset($data['sort_order'])) {
                $data['sort_order'] = (EcommerceHomepageProductRow::max('sort_order') ?? 0) + 1;
            }

            $row = EcommerceHomepageProductRow::create($data);
            $row->load(['category', 'store', 'filter', 'createdBy']);

            return response()->json([
                'response' => 'ok',
                'status' => '201',
                'body' => [
                    'message' => 'Product row created',
                    'row' => $row->toAdminArray(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error creating row: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ecommerce/admin/homepage/rows/{id}
     */
    public function getRow(int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $row = EcommerceHomepageProductRow::with(['category', 'store', 'filter', 'createdBy'])
                ->find($id);

            if (!$row) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Product row not found',
                ], 404);
            }

            $rowData = $row->toAdminArray();

            $rowArticles = $row->allRowArticles()
                ->with(['article.thumbnail', 'article.category', 'customThumbnail'])
                ->get()
                ->map(fn($ra) => $ra->toAdminArray());

            $rowData['row_articles'] = $rowArticles;
            $rowData['preview_articles'] = $row->resolveArticles()->toArray();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['row' => $rowData],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading row: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/ecommerce/admin/homepage/rows/{id}
     */
    public function updateRow(Request $request, int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $row = EcommerceHomepageProductRow::find($id);

            if (!$row) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Product row not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'icon' => 'nullable|string|max:50',
                'row_type' => 'sometimes|string|in:' . implode(',', EcommerceHomepageProductRow::VALID_TYPES),
                'display_style' => 'nullable|string|in:grid,carousel,compact',
                'items_per_row' => 'nullable|integer|min:1|max:6',
                'max_items' => 'nullable|integer|min:1|max:24',
                'category_id' => 'nullable|exists:categories,id',
                'store_id' => 'nullable|exists:stores,id',
                'is_sponsored' => 'nullable|boolean',
                'sponsor_label' => 'nullable|string|max:100',
                'filter_id' => 'nullable|exists:filters,id',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $row->update($validator->validated());
            $row->load(['category', 'store', 'filter', 'createdBy']);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Product row updated',
                    'row' => $row->toAdminArray(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error updating row: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/ecommerce/admin/homepage/rows/{id}
     */
    public function deleteRow(int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $row = EcommerceHomepageProductRow::find($id);

            if (!$row) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Product row not found',
                ], 404);
            }

            if ($row->is_system) {
                return response()->json([
                    'response' => 'error',
                    'status' => '403',
                    'message' => 'Cannot delete system rows. You can deactivate them instead.',
                ], 403);
            }

            $row->delete();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Product row deleted'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error deleting row: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/ecommerce/admin/homepage/rows-reorder
     */
    public function reorderRows(Request $request)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $validator = Validator::make($request->all(), [
                'order' => 'required|array|min:1',
                'order.*' => 'integer|exists:ecommerce_homepage_product_rows,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            DB::transaction(function () use ($request) {
                foreach ($request->order as $index => $rowId) {
                    EcommerceHomepageProductRow::where('id', $rowId)
                        ->update(['sort_order' => $index]);
                }
            });

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Product rows reordered'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error reordering rows: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================================================================================
    //  ROW ARTICLES
    // ==================================================================================

    /**
     * GET /api/ecommerce/admin/homepage/rows/{rowId}/articles
     */
    public function getRowArticles(int $rowId)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $row = EcommerceHomepageProductRow::find($rowId);

            if (!$row) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Product row not found',
                ], 404);
            }

            $rowArticles = $row->allRowArticles()
                ->with(['article.thumbnail', 'article.category', 'article.store', 'customThumbnail'])
                ->get()
                ->map(fn($ra) => $ra->toAdminArray());

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'row_id' => $row->id,
                    'row_title' => $row->title,
                    'row_articles' => $rowArticles,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading row articles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/ecommerce/admin/homepage/rows/{rowId}/articles
     */
    public function addRowArticle(Request $request, int $rowId)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $row = EcommerceHomepageProductRow::find($rowId);

            if (!$row) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Product row not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'article_id' => 'required|exists:articles,id',
                'custom_thumbnail_asset_id' => 'nullable|exists:assets,id',
                'apply_thumbnail_globally' => 'nullable|boolean',
                'custom_title' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $exists = EcommerceHomepageRowArticle::where('ecommerce_homepage_product_row_id', $rowId)
                ->where('article_id', $request->article_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'response' => 'error',
                    'status' => '409',
                    'message' => 'Article already exists in this row',
                ], 409);
            }

            $data = $validator->validated();
            $data['ecommerce_homepage_product_row_id'] = $rowId;

            if (!isset($data['sort_order'])) {
                $data['sort_order'] = ($row->allRowArticles()->max('sort_order') ?? 0) + 1;
            }

            $rowArticle = EcommerceHomepageRowArticle::create($data);
            $rowArticle->load(['article.thumbnail', 'article.category', 'customThumbnail']);

            return response()->json([
                'response' => 'ok',
                'status' => '201',
                'body' => [
                    'message' => 'Article added to row',
                    'row_article' => $rowArticle->toAdminArray(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error adding article to row: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/ecommerce/admin/homepage/rows/{rowId}/articles/{articleId}
     */
    public function updateRowArticle(Request $request, int $rowId, int $articleId)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $rowArticle = EcommerceHomepageRowArticle::where('ecommerce_homepage_product_row_id', $rowId)
                ->where('article_id', $articleId)
                ->first();

            if (!$rowArticle) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Row article not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'custom_thumbnail_asset_id' => 'nullable|exists:assets,id',
                'apply_thumbnail_globally' => 'nullable|boolean',
                'custom_title' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $rowArticle->update($validator->validated());
            $rowArticle->load(['article.thumbnail', 'article.category', 'customThumbnail']);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'message' => 'Row article updated',
                    'row_article' => $rowArticle->toAdminArray(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error updating row article: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/ecommerce/admin/homepage/rows/{rowId}/articles/{articleId}
     */
    public function removeRowArticle(int $rowId, int $articleId)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $rowArticle = EcommerceHomepageRowArticle::where('ecommerce_homepage_product_row_id', $rowId)
                ->where('article_id', $articleId)
                ->first();

            if (!$rowArticle) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Row article not found',
                ], 404);
            }

            $rowArticle->delete();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Article removed from row'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error removing article from row: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/ecommerce/admin/homepage/rows/{rowId}/articles-reorder
     */
    public function reorderRowArticles(Request $request, int $rowId)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $row = EcommerceHomepageProductRow::find($rowId);

            if (!$row) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Product row not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'order' => 'required|array|min:1',
                'order.*' => 'integer|exists:ecommerce_homepage_row_articles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            DB::transaction(function () use ($request) {
                foreach ($request->order as $index => $rowArticleId) {
                    EcommerceHomepageRowArticle::where('id', $rowArticleId)
                        ->update(['sort_order' => $index]);
                }
            });

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Row articles reordered'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error reordering row articles: ' . $e->getMessage(),
            ], 500);
        }
    }
}