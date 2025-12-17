<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table tracks all manual PV adjustments made by admin/backoffice users.
     * Essential for audit trail and accountability.
     */
    public function up(): void
    {
        Schema::create('wallet_adjustments', function (Blueprint $table) {
            $table->id();
            
            // User whose wallet is being adjusted
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User whose wallet is adjusted');
            
            // Admin who made the adjustment
            $table->foreignId('adjusted_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Admin/backoffice user who made the adjustment');
            
            // Type of adjustment
            $table->enum('adjustment_type', [
                'punti_valore_maturati',
                'punti_bonus',
                'punti_carriera_maturati',
                'punti_spesi'
            ])->comment('Which PV field was adjusted');
            
            // Amount (positive = add, negative = subtract)
            $table->integer('amount')
                ->comment('Adjustment amount (positive=add, negative=subtract)');
            
            // Balance before adjustment
            $table->integer('balance_before')
                ->comment('Balance before this adjustment');
            
            // Balance after adjustment
            $table->integer('balance_after')
                ->comment('Balance after this adjustment');
            
            // Reason for adjustment (required)
            $table->string('reason', 500)
                ->comment('Reason for this adjustment (required for audit)');
            
            // Internal notes (optional)
            $table->text('internal_notes')
                ->nullable()
                ->comment('Additional internal notes');
            
            // Whether email notification was sent
            $table->boolean('notification_sent')
                ->default(false)
                ->comment('Whether email notification was sent to user');
            
            // Email sent at timestamp
            $table->timestamp('notification_sent_at')
                ->nullable();
            
            // IP address of admin for security audit
            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP address of admin who made the adjustment');
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('user_id', 'wallet_adjustments_user_index');
            $table->index('adjusted_by_user_id', 'wallet_adjustments_admin_index');
            $table->index('adjustment_type', 'wallet_adjustments_type_index');
            $table->index('created_at', 'wallet_adjustments_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_adjustments');
    }
};