<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // E-commerce essential seeders
        $this->call([
            // 1. Base tables
            StoreSeeder::class,
            CartStatusSeeder::class,
            OrderStatusSeeder::class,
            PaymentModeSeeder::class,
            
            // 2. Filters and Categories
            FilterSeeder::class, 
            CategorySeeder::class,
            
            // 3. Products
            ArticleSeeder::class,
            StockSeeder::class,
        ]);

        $this->command->info('E-commerce seeders completed!');
    }
}