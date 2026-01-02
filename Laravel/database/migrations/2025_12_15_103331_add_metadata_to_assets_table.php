<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds metadata fields to assets for better file management.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Alt text for accessibility and SEO
            $table->string('alt_text')
                  ->nullable()
                  ->after('file_name')
                  ->comment('Alternative text for images');
            
            // File metadata
            $table->string('mime_type')
                  ->nullable()
                  ->after('file_type')
                  ->comment('MIME type, e.g.: image/jpeg, video/mp4');
            
            $table->unsignedBigInteger('file_size')
                  ->nullable()
                  ->after('mime_type')
                  ->comment('File size in bytes');
            
            // Image dimensions (if applicable)
            $table->unsignedInteger('width')
                  ->nullable()
                  ->after('file_size')
                  ->comment('Image width in pixels');
            
            $table->unsignedInteger('height')
                  ->nullable()
                  ->after('width')
                  ->comment('Image height in pixels');
            
            // Original filename before upload processing
            $table->string('original_name')
                  ->nullable()
                  ->after('height')
                  ->comment('Original filename as uploaded');
            
            // Disk/storage location
            $table->string('disk')
                  ->default('public')
                  ->after('file_path')
                  ->comment('Storage disk name');
            
            // Status
            $table->boolean('is_active')
                  ->default(true)
                  ->after('display_order');
            
            // Uploader info
            $table->unsignedBigInteger('uploaded_by_user_id')
                  ->nullable()
                  ->after('is_active');
            
            $table->foreign('uploaded_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Indexes
            $table->index('mime_type');
            $table->index('is_active');
            $table->index('uploaded_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by_user_id']);
            $table->dropColumn([
                'alt_text',
                'mime_type',
                'file_size',
                'width',
                'height',
                'original_name',
                'disk',
                'is_active',
                'uploaded_by_user_id'
            ]);
        });
    }
};
