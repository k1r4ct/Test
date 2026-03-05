<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create homepage_slides table.
     * 
     * Stores hero carousel slides that BO can manage from admin panel.
     * Replaces the hardcoded carouselSlides[] in ecommerce.component.ts.
     */
    public function up(): void
    {
        Schema::create('ecommerce_homepage_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Badge
            $table->string('badge_text', 100)->nullable()
                  ->comment('Badge label, e.g. "Disponibile Ora"');
            $table->string('badge_icon', 50)->nullable()
                  ->comment('Optional Material icon name for badge');

            // Call-to-action
            $table->string('cta_text', 100)->nullable()
                  ->comment('Button text, e.g. "Scopri i Buoni"');
            $table->string('cta_action', 50)->default('scroll-catalog')
                  ->comment('Action: scroll-catalog, open-wallet, link, coming-soon');
            $table->string('cta_url', 500)->nullable()
                  ->comment('Target URL when cta_action = link');
            $table->boolean('cta_disabled')->default(false);

            // Image: from assets table or external URL fallback
            $table->unsignedBigInteger('image_asset_id')->nullable()
                  ->comment('FK to assets table for uploaded slide image');
            $table->foreign('image_asset_id')
                  ->references('id')->on('assets')
                  ->onDelete('set null');
            $table->string('image_url', 500)->nullable()
                  ->comment('Fallback external URL if no asset uploaded');

            // Visual settings
            $table->string('gradient', 255)
                  ->default('linear-gradient(135deg, #667eea 0%, #764ba2 100%)')
                  ->comment('CSS gradient or solid color for slide background');

            // Ordering and visibility
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Scheduling
            $table->timestamp('starts_at')->nullable()
                  ->comment('Show slide only after this date');
            $table->timestamp('ends_at')->nullable()
                  ->comment('Hide slide after this date');

            // Audit
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->foreign('updated_by_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('sort_order', 'idx_slides_sort_order');
            $table->index(['is_active', 'starts_at', 'ends_at'], 'idx_slides_active_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_homepage_slides');
    }
};