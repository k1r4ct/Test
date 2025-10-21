<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('article_name');
            $table->text('description')->nullable();
            $table->integer('pv_price');
            $table->boolean('available')->default(true);
            
            // Foreign Keys
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            
            $table->unsignedBigInteger('filter_id');
            $table->foreign('filter_id')->references('id')->on('filters');
            
            $table->unsignedBigInteger('store_id');
            $table->foreign('store_id')->references('id')->on('stores');
            
            $table->unsignedBigInteger('thumbnail_asset_id')->nullable();
            $table->foreign('thumbnail_asset_id')->references('id')->on('assets');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};