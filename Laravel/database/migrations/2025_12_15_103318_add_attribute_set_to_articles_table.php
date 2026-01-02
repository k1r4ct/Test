<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds attribute_set_id to articles table.
     * This determines which attributes are available for the article.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Add attribute_set_id after store_id
            $table->unsignedBigInteger('attribute_set_id')
                  ->nullable()
                  ->after('store_id')
                  ->comment('Determines which attributes this article uses');
            
            $table->foreign('attribute_set_id')
                  ->references('id')
                  ->on('attribute_sets')
                  ->onDelete('set null');
            
            // Add euro_price for displaying real value (especially for gift cards)
            $table->decimal('euro_price', 10, 2)
                  ->nullable()
                  ->after('pv_price')
                  ->comment('Price in euros, useful for gift cards showing real value');
            
            // Add sort_order for manual ordering in catalog
            $table->integer('sort_order')
                  ->default(0)
                  ->after('available');
            
            // Add featured flag for highlighting products
            $table->boolean('is_featured')
                  ->default(false)
                  ->after('sort_order');
            
            // Indexes
            $table->index('attribute_set_id');
            $table->index('sort_order');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['attribute_set_id']);
            $table->dropColumn(['attribute_set_id', 'euro_price', 'sort_order', 'is_featured']);
        });
    }
};
