<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Rename pv_temporanei to pv_bloccati in cart_items table
     * Using CHANGE COLUMN for compatibility with older MySQL/MariaDB versions
     */
    public function up(): void
    {
        // Use raw SQL with CHANGE COLUMN (compatible with older versions)
        DB::statement('ALTER TABLE `cart_items` CHANGE `pv_temporanei` `pv_bloccati` INT(11) NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: rename back to pv_temporanei
        DB::statement('ALTER TABLE `cart_items` CHANGE `pv_bloccati` `pv_temporanei` INT(11) NULL DEFAULT NULL');
    }
};