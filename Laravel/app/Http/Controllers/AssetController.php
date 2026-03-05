<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * AssetController
 * 
 * Handles file upload, media library browsing, and asset deletion.
 * Reuses the existing Asset::createFromUpload() static method.
 * 
 * Access: role_id 1 (Admin) and 5 (BackOffice)
 */
class AssetController extends Controller
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

    /**
     * Upload a file and create an asset record.
     * 
     * POST /api/ecommerce/admin/assets/upload
     * 
     * Accepts: file (required), usage_context (optional), alt_text (optional)
     * Uses Asset::createFromUpload() for consistent file handling.
     */
    public function upload(Request $request)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240', // 10MB max
                'usage_context' => 'nullable|string|max:50',
                'alt_text' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $file = $request->file('file');
            $usageContext = $request->input('usage_context', 'general');

            // Determine storage directory based on usage context
            $directory = $this->getDirectoryForContext($usageContext);

            // Use existing Asset::createFromUpload() method
            $asset = Asset::createFromUpload(
                $file,
                $directory,
                Auth::id()
            );

            // Update optional fields
            $asset->update([
                'usage_context' => $usageContext,
                'alt_text' => $request->input('alt_text'),
            ]);

            return response()->json([
                'response' => 'ok',
                'status' => '201',
                'body' => [
                    'message' => 'File uploaded successfully',
                    'asset' => [
                        'id' => $asset->id,
                        'file_name' => $asset->file_name,
                        'original_name' => $asset->original_name,
                        'file_type' => $asset->file_type,
                        'mime_type' => $asset->mime_type,
                        'file_size' => $asset->file_size,
                        'file_size_formatted' => $asset->getFormattedFileSize(),
                        'width' => $asset->width,
                        'height' => $asset->height,
                        'url' => $asset->getUrl(),
                        'usage_context' => $asset->usage_context,
                        'alt_text' => $asset->alt_text,
                        'created_at' => $asset->created_at?->format('d/m/Y H:i'),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Browse media library with optional filters.
     * 
     * GET /api/ecommerce/admin/assets
     * 
     * Query params: type (image|video|document), usage_context, search, per_page
     */
    public function index(Request $request)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $query = Asset::active()->ordered();

            // Filter by file type
            if ($request->has('type')) {
                $query->where('file_type', $request->type);
            }

            // Filter by usage context
            if ($request->has('usage_context')) {
                $query->where('usage_context', $request->usage_context);
            }

            // Search by file name
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('file_name', 'LIKE', "%{$search}%")
                      ->orWhere('original_name', 'LIKE', "%{$search}%")
                      ->orWhere('alt_text', 'LIKE', "%{$search}%");
                });
            }

            // Filter by mime type
            if ($request->has('mime_type')) {
                $query->byMimeType($request->mime_type);
            }

            $perPage = min($request->input('per_page', 24), 100);
            $assets = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'assets' => $assets->map(function ($asset) {
                        return [
                            'id' => $asset->id,
                            'file_name' => $asset->file_name,
                            'original_name' => $asset->original_name,
                            'file_type' => $asset->file_type,
                            'mime_type' => $asset->mime_type,
                            'file_size' => $asset->file_size,
                            'file_size_formatted' => $asset->getFormattedFileSize(),
                            'width' => $asset->width,
                            'height' => $asset->height,
                            'dimensions' => $asset->getDimensions(),
                            'url' => $asset->getUrl(),
                            'usage_context' => $asset->usage_context,
                            'alt_text' => $asset->alt_text,
                            'created_at' => $asset->created_at?->format('d/m/Y H:i'),
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $assets->currentPage(),
                        'last_page' => $assets->lastPage(),
                        'per_page' => $assets->perPage(),
                        'total' => $assets->total(),
                    ],
                    'stats' => Asset::getStorageStats(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading assets: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single asset detail.
     * 
     * GET /api/ecommerce/admin/assets/{id}
     */
    public function show(int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $asset = Asset::with('uploadedBy')->find($id);

            if (!$asset) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Asset not found',
                ], 404);
            }

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'asset' => [
                        'id' => $asset->id,
                        'file_name' => $asset->file_name,
                        'original_name' => $asset->original_name,
                        'file_type' => $asset->file_type,
                        'mime_type' => $asset->mime_type,
                        'file_size' => $asset->file_size,
                        'file_size_formatted' => $asset->getFormattedFileSize(),
                        'width' => $asset->width,
                        'height' => $asset->height,
                        'dimensions' => $asset->getDimensions(),
                        'url' => $asset->getUrl(),
                        'full_path' => $asset->file_path,
                        'disk' => $asset->disk,
                        'usage_context' => $asset->usage_context,
                        'alt_text' => $asset->alt_text,
                        'is_active' => $asset->is_active,
                        'uploaded_by' => $asset->uploadedBy ? [
                            'id' => $asset->uploadedBy->id,
                            'name' => $asset->uploadedBy->name . ' ' . $asset->uploadedBy->cognome,
                        ] : null,
                        'file_exists' => $asset->fileExists(),
                        'created_at' => $asset->created_at?->format('d/m/Y H:i'),
                        'updated_at' => $asset->updated_at?->format('d/m/Y H:i'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading asset: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update asset metadata (alt_text, usage_context, is_active).
     * 
     * PUT /api/ecommerce/admin/assets/{id}
     */
    public function update(Request $request, int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $asset = Asset::find($id);

            if (!$asset) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Asset not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'alt_text' => 'nullable|string|max:255',
                'usage_context' => 'nullable|string|max:50',
                'is_active' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'error',
                    'status' => '400',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $asset->update($validator->validated());

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Asset updated'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error updating asset: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an asset (removes file from storage too via model event).
     * 
     * DELETE /api/ecommerce/admin/assets/{id}
     */
    public function destroy(int $id)
    {
        if ($error = $this->checkPermission()) return $error;

        try {
            $asset = Asset::find($id);

            if (!$asset) {
                return response()->json([
                    'response' => 'error',
                    'status' => '404',
                    'message' => 'Asset not found',
                ], 404);
            }

            // The model's deleting event handles file deletion
            $asset->delete();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => ['message' => 'Asset deleted'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error deleting asset: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determine storage directory based on usage context.
     */
    private function getDirectoryForContext(string $context): string
    {
        return match ($context) {
            'product_thumbnail' => 'assets/products/thumbnails',
            'product_gallery' => 'assets/products/gallery',
            'slide_image' => 'assets/slides',
            'store_logo' => 'assets/stores/logos',
            'category_image' => 'assets/categories',
            'cms_content' => 'assets/cms',
            default => 'assets/general',
        };
    }
}