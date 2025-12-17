<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Attributes define the characteristics that can be assigned to articles.
     * Uses EAV (Entity-Attribute-Value) pattern for flexible product attributes.
     */
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            
            // Attribute identification
            $table->string('attribute_code')->unique()->comment('Unique identifier, e.g.: validity, euro_value, format');
            $table->string('attribute_name')->comment('Display name, e.g.: Validità, Valore in Euro');
            $table->text('description')->nullable();
            
            // Attribute type determines how value is stored and rendered
            $table->enum('attribute_type', [
                'text',      // Short text (varchar)
                'textarea',  // Long text
                'number',    // Integer
                'decimal',   // Decimal number (prices, percentages)
                'boolean',   // Yes/No
                'select',    // Single selection from options
                'multiselect', // Multiple selection
                'date',      // Date only
                'datetime',  // Date and time
                'price'      // Currency value (stored as decimal, displayed with €)
            ])->default('text');
            
            // For select/multiselect: JSON array of options
            // Example: ["Immediata", "24 ore", "48 ore"]
            $table->json('options')->nullable()->comment('Options for select/multiselect types');
            
            // Validation rules
            $table->boolean('is_required')->default(false);
            $table->string('validation_rules')->nullable()->comment('Laravel validation rules, e.g.: min:0|max:1000');
            $table->string('default_value')->nullable();
            
            // Display settings
            $table->integer('sort_order')->default(0)->comment('Order in forms and display');
            $table->boolean('is_visible_on_front')->default(true)->comment('Show on product detail page');
            $table->boolean('is_filterable')->default(false)->comment('Can be used as filter in catalog');
            $table->boolean('is_searchable')->default(false)->comment('Include in search index');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('attribute_code');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
