<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create homepage_row_articles table.
     * 
     * Pivot table linking specific articles to homepage product rows.
     * Used for:
     * - Manual rows: BO picks exactly which articles to show
     * - Override rows: BO overrides automatic selection for any row type
     * - Custom thumbnails: BO can upload a custom thumb per article per row
     * - Custom titles: BO can override article title for display in that row
     * 
     * When a row has entries here, they take priority over automatic selection.
     * For store_showcase rows, automatic = all store articles;
     * entries here override that with a manual selection.
     */
    public function up(): void
    {
        Schema::create('ecommerce_homepage_row_articles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ecommerce_homepage_product_row_id');
            $table->foreign('ecommerce_homepage_product_row_id', 'fk_row_articles_row')
                  ->references('id')->on('ecommerce_homepage_product_rows')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('article_id');
            $table->foreign('article_id', 'fk_row_articles_article')
                  ->references('id')->on('articles')
                  ->onDelete('cascade');

            // Custom thumbnail for this article in this row context
            $table->unsignedBigInteger('custom_thumbnail_asset_id')->nullable()
                  ->comment('Custom thumbnail from assets table');
            $table->foreign('custom_thumbnail_asset_id', 'fk_row_articles_thumb')
                  ->references('id')->on('assets')
                  ->onDelete('set null');

            // Whether the custom thumbnail should replace the article global thumbnail
            $table->boolean('apply_thumbnail_globally')->default(false)
                  ->comment('If true, also updates article.thumbnail_asset_id');

            // Optional title override
            $table->string('custom_title', 255)->nullable()
                  ->comment('Override article title for this row only');

            // Ordering within the row
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Each article can appear only once per row
            $table->unique(
                ['ecommerce_homepage_product_row_id', 'article_id'],
                'unique_row_article'
            );
            $table->index('sort_order', 'idx_row_articles_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_homepage_row_articles');
    }
};