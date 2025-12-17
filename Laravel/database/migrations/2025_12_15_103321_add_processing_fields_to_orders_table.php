<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds fields for direct order management by backoffice.
     * Replaces ticket-based workflow with direct order processing.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Processing user (backoffice who handles the order)
            $table->unsignedBigInteger('processed_by_user_id')
                  ->nullable()
                  ->after('payment_method_id')
                  ->comment('Backoffice user who processed/is processing this order');
            
            $table->foreign('processed_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Processing timestamps
            $table->timestamp('processing_started_at')
                  ->nullable()
                  ->after('processed_by_user_id')
                  ->comment('When order was taken in charge');
            
            $table->timestamp('processed_at')
                  ->nullable()
                  ->after('processing_started_at')
                  ->comment('When order was completed/fulfilled');
            
            // Notes and communication
            $table->text('admin_notes')
                  ->nullable()
                  ->after('processed_at')
                  ->comment('Internal notes visible only to backoffice/admin');
            
            $table->text('customer_message')
                  ->nullable()
                  ->after('admin_notes')
                  ->comment('Message sent to customer upon completion');
            
            // Customer info at time of order (for historical purposes)
            $table->string('customer_email')
                  ->nullable()
                  ->after('customer_message')
                  ->comment('Customer email at time of order');
            
            $table->string('customer_name')
                  ->nullable()
                  ->after('customer_email')
                  ->comment('Customer name at time of order');
            
            // Priority for order queue
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                  ->default('normal')
                  ->after('order_status_id');
            
            // Cancellation info
            $table->text('cancellation_reason')
                  ->nullable()
                  ->after('customer_name');
            
            $table->timestamp('cancelled_at')
                  ->nullable()
                  ->after('cancellation_reason');
            
            // Indexes
            $table->index('processed_by_user_id');
            $table->index('priority');
            $table->index('processing_started_at');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['processed_by_user_id']);
            $table->dropColumn([
                'processed_by_user_id',
                'processing_started_at',
                'processed_at',
                'admin_notes',
                'customer_message',
                'customer_email',
                'customer_name',
                'priority',
                'cancellation_reason',
                'cancelled_at'
            ]);
        });
    }
};
