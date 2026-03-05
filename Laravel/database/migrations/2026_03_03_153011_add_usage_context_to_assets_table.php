<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add usage_context to assets table.
     * 
     * Allows filtering assets by their purpose in the media library.
     * Examples: 'product_thumbnail', 'product_gallery', 'slide_image',
     *           'store_logo', 'category_image', 'cms_content', 'general'
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('usage_context', 50)
                  ->default('general')
                  ->after('is_active')
                  ->comment('Asset purpose: product_thumbnail, product_gallery, slide_image, store_logo, category_image, cms_content, general');

            $table->index('usage_context', 'idx_assets_usage_context');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('idx_assets_usage_context');
            $table->dropColumn('usage_context');
        });
    }
};