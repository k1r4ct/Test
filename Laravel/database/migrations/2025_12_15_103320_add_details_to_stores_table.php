<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds additional details and display options to stores.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Store description
            $table->text('description')
                  ->nullable()
                  ->after('store_type');
            
            // Logo/branding
            $table->unsignedBigInteger('logo_asset_id')
                  ->nullable()
                  ->after('description')
                  ->comment('Store logo from assets table');
            
            $table->foreign('logo_asset_id')
                  ->references('id')
                  ->on('assets')
                  ->onDelete('set null');
            
            // Store banner image
            $table->string('banner_path')
                  ->nullable()
                  ->after('logo_asset_id');
            
            // Display settings
            $table->integer('sort_order')
                  ->default(0)
                  ->after('active');
            
            // Contact/info (useful for future multi-vendor)
            $table->string('contact_email')
                  ->nullable()
                  ->after('sort_order');
            
            // SEO
            $table->string('slug')
                  ->nullable()
                  ->after('store_name')
                  ->comment('URL-friendly identifier');
            
            // Timestamps (stores table doesn't have them currently)
            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['logo_asset_id']);
            $table->dropColumn([
                'description',
                'logo_asset_id',
                'banner_path',
                'sort_order',
                'contact_email',
                'slug',
                'created_at',
                'updated_at'
            ]);
        });
    }
};
