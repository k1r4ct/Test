<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create ticket_changes_log table to track ALL ticket changes
     * This includes status changes and priority changes
     * Each change creates a NEW row (full history)
     */
    public function up(): void
    {
        Schema::create('ticket_changes_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id'); // Who made the change
            
            // Status changes
            $table->enum('previous_status', ['new', 'waiting', 'resolved', 'closed', 'deleted'])->nullable();
            $table->enum('new_status', ['new', 'waiting', 'resolved', 'closed', 'deleted'])->nullable();
            
            // Priority changes
            $table->enum('previous_priority', ['low', 'medium', 'high', 'unassigned'])->nullable();
            $table->enum('new_priority', ['low', 'medium', 'high', 'unassigned'])->nullable();
            
            // Type of change for easy filtering
            $table->enum('change_type', ['status', 'priority', 'both'])->default('status');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for faster queries
            $table->index('ticket_id');
            $table->index('user_id');
            $table->index('change_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_changes_log');
    }
};