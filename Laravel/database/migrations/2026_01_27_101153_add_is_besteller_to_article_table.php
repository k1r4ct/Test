<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_bestseller')
                  ->default(false)
                  ->after('is_featured')
                  ->comment('Displays BEST SELLER badge on product card');
            
            $table->index('is_bestseller');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['is_bestseller']);
            $table->dropColumn('is_bestseller');
        });
    }
};