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
        Schema::table('ticket_messages', function (Blueprint $table) {
            // Add new column for attachment flag
            $table->boolean('has_attachments')->default(false)->after('message');
            
            // Drop old attachment-related columns
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_messages', function (Blueprint $table) {
            // Restore old columns
            $table->string('attachment_path', 255)->nullable()->after('message_type');
            $table->string('attachment_name', 255)->nullable()->after('attachment_path');
            $table->integer('attachment_size')->nullable()->after('attachment_name');
            
            // Remove new column
            $table->dropColumn('has_attachments');
        });
    }
};