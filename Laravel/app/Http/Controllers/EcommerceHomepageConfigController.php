<?php

namespace App\Http\Controllers;

use App\Models\EcommerceHomepageSlide;
use App\Models\EcommerceHomepageProductRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * EcommerceHomepageConfigController
 * 
 * Consumer endpoint that returns the homepage configuration:
 * - Hero carousel slides
 * - Product rows with resolved articles
 * 
 * GET /api/ecommerce/homepage/config
 */
class EcommerceHomepageConfigController extends Controller
{
    /**
     * Get full homepage configuration for the consumer frontend.
     */
    public function getConfig()
    {
        try {
            $user = Auth::user();

            // ── Slides ──
            $slides = EcommerceHomepageSlide::with('imageAsset')
                ->visible()
                ->ordered()
                ->get()
                ->map(fn($slide) => $slide->toConsumerArray())
                ->values();

            // ── Product Rows ──
            $rows = EcommerceHomepageProductRow::with(['category', 'store', 'filter'])
                ->active()
                ->ordered()
                ->get()
                ->filter(fn($row) => $row->isVisibleToUser($user))
                ->map(fn($row) => $row->toConsumerArray($user))
                ->filter(fn($row) => count($row['articles']) > 0)
                ->values();

            return response()->json([
                'response' => 'ok',
                'status' => '200',
                'body' => [
                    'slides' => $slides,
                    'product_rows' => $rows,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'status' => '500',
                'message' => 'Error loading homepage config: ' . $e->getMessage(),
            ], 500);
        }
    }
}