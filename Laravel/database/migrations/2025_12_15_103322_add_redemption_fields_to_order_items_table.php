<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds fields for gift card/voucher redemption codes and item-level notes.
     * Each order item can have its own redemption code (for gift cards).
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Redemption code for digital products (gift cards, vouchers)
            $table->string('redemption_code')
                  ->nullable()
                  ->after('pv_total_price')
                  ->comment('Gift card/voucher code provided upon fulfillment');
            
            // Status of this specific item
            $table->enum('item_status', ['pending', 'processing', 'fulfilled', 'cancelled', 'refunded'])
                  ->default('pending')
                  ->after('redemption_code');
            
            // When the code was provided
            $table->timestamp('fulfilled_at')
                  ->nullable()
                  ->after('item_status')
                  ->comment('When redemption code was provided');
            
            // Who fulfilled this item
            $table->unsignedBigInteger('fulfilled_by_user_id')
                  ->nullable()
                  ->after('fulfilled_at');
            
            $table->foreign('fulfilled_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Internal note for backoffice (per item)
            $table->text('internal_note')
                  ->nullable()
                  ->after('fulfilled_by_user_id')
                  ->comment('Internal note for this specific item');
            
            // Customer message for this item (if different from order level)
            $table->text('customer_note')
                  ->nullable()
                  ->after('internal_note')
                  ->comment('Note to customer for this specific item');
            
            // Store article name at time of purchase (for historical records)
            $table->string('article_name_snapshot')
                  ->nullable()
                  ->after('article_id')
                  ->comment('Article name at time of purchase');
            
            $table->string('article_sku_snapshot')
                  ->nullable()
                  ->after('article_name_snapshot')
                  ->comment('Article SKU at time of purchase');
            
            // Indexes
            $table->index('redemption_code');
            $table->index('item_status');
            $table->index('fulfilled_at');
            $table->index('fulfilled_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['fulfilled_by_user_id']);
            $table->dropColumn([
                'redemption_code',
                'item_status',
                'fulfilled_at',
                'fulfilled_by_user_id',
                'internal_note',
                'customer_note',
                'article_name_snapshot',
                'article_sku_snapshot'
            ]);
        });
    }
};
