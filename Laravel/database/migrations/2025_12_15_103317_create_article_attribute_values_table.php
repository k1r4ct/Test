<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * EAV (Entity-Attribute-Value) table for article attributes.
     * Each row stores one attribute value for one article.
     * Multiple columns for different data types to maintain proper indexing and querying.
     */
    public function up(): void
    {
        Schema::create('article_attribute_values', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('article_id');
            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');
            
            $table->unsignedBigInteger('attribute_id');
            $table->foreign('attribute_id')
                  ->references('id')
                  ->on('attributes')
                  ->onDelete('cascade');
            
            // Value columns - only one will be used based on attribute_type
            $table->string('value_text')->nullable()->comment('For text, select types');
            $table->text('value_textarea')->nullable()->comment('For textarea type');
            $table->integer('value_integer')->nullable()->comment('For number type');
            $table->decimal('value_decimal', 12, 4)->nullable()->comment('For decimal, price types');
            $table->boolean('value_boolean')->nullable()->comment('For boolean type');
            $table->date('value_date')->nullable()->comment('For date type');
            $table->datetime('value_datetime')->nullable()->comment('For datetime type');
            $table->json('value_json')->nullable()->comment('For multiselect and complex data');
            
            $table->timestamps();
            
            // Unique constraint: one value per article per attribute
            $table->unique(['article_id', 'attribute_id'], 'article_attribute_unique');
            
            // Indexes for filtering and searching
            $table->index('article_id');
            $table->index('attribute_id');
            $table->index('value_text');
            $table->index('value_integer');
            $table->index('value_decimal');
            $table->index('value_boolean');
            $table->index('value_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_attribute_values');
    }
};
