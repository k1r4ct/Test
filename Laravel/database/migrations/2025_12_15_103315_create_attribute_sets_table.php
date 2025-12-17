<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Attribute Sets group related attributes together.
     * Example: "Buoni Digitali" set contains: validity, euro_value, delivery_method
     * Example: "Prodotti Fisici" set contains: weight, dimensions, shipping_cost
     */
    public function up(): void
    {
        Schema::create('attribute_sets', function (Blueprint $table) {
            $table->id();
            
            $table->string('set_name')->unique()->comment('Name of the attribute set');
            $table->text('description')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_sets');
    }
};
