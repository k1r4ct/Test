<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create homepage_product_rows table.
     * 
     * Stores the configuration for product row sections on the homepage.
     * Replaces hardcoded productRowsConfig[] in ecommerce.component.ts.
     * 
     * Each row can be:
     * - featured: Shows articles with is_featured=true
     * - new_arrivals: Shows most recent articles
     * - bestsellers: Shows best-selling articles
     * - category: Shows articles from a specific category
     * - store_showcase: Shows articles from a client's store (sponsored)
     * - manual: Shows manually selected articles (via homepage_row_articles)
     */
    public function up(): void
    {
        Schema::create('ecommerce_homepage_product_rows', function (Blueprint $table) {
            $table->id();
            $table->string('row_key', 100)->unique()
                  ->comment('Unique identifier for the row, e.g. featured, category_giftcards');
            $table->string('title', 255)
                  ->comment('Display title, e.g. "In Evidenza", "Gift Cards Amazon"');
            $table->string('icon', 50)->default('shopping_bag')
                  ->comment('Material icon name for the row header');

            // Row type determines how articles are selected
            $table->enum('row_type', [
                'featured',
                'new_arrivals',
                'bestsellers',
                'category',
                'store_showcase',
                'manual'
            ])->default('manual');

            // Display settings
            $table->enum('display_style', ['grid', 'carousel', 'compact'])->default('grid');
            $table->tinyInteger('items_per_row')->unsigned()->default(4)
                  ->comment('Number of columns in grid layout');
            $table->tinyInteger('max_items')->unsigned()->default(8)
                  ->comment('Maximum articles to show in this row');

            // Optional FK for category-based rows
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('set null');

            // Optional FK for store showcase rows
            $table->unsignedBigInteger('store_id')->nullable();
            $table->foreign('store_id')
                  ->references('id')->on('stores')
                  ->onDelete('set null');

            // Sponsored store showcase settings
            $table->boolean('is_sponsored')->default(false)
                  ->comment('Whether this is a paid showcase row');
            $table->string('sponsor_label', 100)->nullable()
                  ->comment('Label for sponsored rows, e.g. "In Vetrina", "Partner"');

            // Visibility filter (role-based)
            $table->unsignedBigInteger('filter_id')->nullable();
            $table->foreign('filter_id')
                  ->references('id')->on('filters')
                  ->onDelete('set null');

            // Ordering and status
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)
                  ->comment('System rows (featured, new_arrivals) cannot be deleted');

            // Audit
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('sort_order', 'idx_rows_sort_order');
            $table->index('is_active', 'idx_rows_active');
            $table->index('row_type', 'idx_rows_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_homepage_product_rows');
    }
};