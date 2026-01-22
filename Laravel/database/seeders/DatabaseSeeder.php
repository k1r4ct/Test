<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder
 * 
 * This seeder orchestrates all seeders for the Semprechiaro CRM.
 * 
 * IMPORTANT: E-commerce seeders have been consolidated into EcommerceSeeder.
 * The following individual seeders are DEPRECATED and should be deleted:
 * - StoreSeeder.php
 * - CartStatusSeeder.php
 * - OrderStatusSeeder.php
 * - FilterSeeder.php
 * - CategorySeeder.php
 * - ArticleSeeder.php
 * - StockSeeder.php
 * - PaymentModeSeeder.php (if exists)
 * 
 * Run with: php artisan db:seed
 * Run specific: php artisan db:seed --class=EcommerceSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // =====================================================
        // CORE BUSINESS SEEDERS (keep these)
        // =====================================================
        
        // Uncomment if you need to seed these tables:
        // $this->call([
        //     MacroProductSeeder::class,
        //     ProductSeeder::class,
        //     SupplierSeeder::class,
        //     SupplierCategorySeeder::class,
        // ]);

        // =====================================================
        // E-COMMERCE SEEDER (consolidated - all in one)
        // =====================================================
        $this->call([
            EcommerceSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✅ Database seeding completed!');
        $this->command->info('');
    }
}
