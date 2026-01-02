<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Pivot table that defines which attributes belong to which attribute set.
     * Allows the same attribute to be in multiple sets with different sort orders.
     */
    public function up(): void
    {
        Schema::create('attribute_set_attributes', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('attribute_set_id');
            $table->foreign('attribute_set_id')
                  ->references('id')
                  ->on('attribute_sets')
                  ->onDelete('cascade');
            
            $table->unsignedBigInteger('attribute_id');
            $table->foreign('attribute_id')
                  ->references('id')
                  ->on('attributes')
                  ->onDelete('cascade');
            
            // Sort order within the set (can differ from attribute's global sort_order)
            $table->integer('sort_order')->default(0);
            
            // Override required status for this specific set
            $table->boolean('is_required')->nullable()->comment('Override attribute default required status');
            
            $table->timestamps();
            
            // Unique constraint: attribute can only be in a set once
            $table->unique(['attribute_set_id', 'attribute_id'], 'attr_set_attr_unique');
            
            // Indexes
            $table->index('attribute_set_id');
            $table->index('attribute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_set_attributes');
    }
};
