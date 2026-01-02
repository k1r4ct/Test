<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds hierarchical structure and display options to categories.
     * Allows nested categories (parent > child) and better catalog organization.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Parent category for hierarchy (null = root category)
            $table->unsignedBigInteger('parent_id')
                  ->nullable()
                  ->after('id')
                  ->comment('Parent category ID for nested structure');
            
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null');
            
            // Display options
            $table->string('icon')
                  ->nullable()
                  ->after('description')
                  ->comment('Icon class or path for category display');
            
            $table->string('image_path')
                  ->nullable()
                  ->after('icon')
                  ->comment('Category banner/thumbnail image');
            
            $table->integer('sort_order')
                  ->default(0)
                  ->after('image_path')
                  ->comment('Display order in navigation');
            
            // Status
            $table->boolean('is_active')
                  ->default(true)
                  ->after('sort_order');
            
            // SEO and display
            $table->string('slug')
                  ->nullable()
                  ->after('category_name')
                  ->comment('URL-friendly identifier');
            
            $table->string('meta_title')
                  ->nullable()
                  ->after('is_active');
            
            $table->text('meta_description')
                  ->nullable()
                  ->after('meta_title');
            
            // Indexes
            $table->index('parent_id');
            $table->index('sort_order');
            $table->index('is_active');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id',
                'icon',
                'image_path',
                'sort_order',
                'is_active',
                'slug',
                'meta_title',
                'meta_description'
            ]);
        });
    }
};
