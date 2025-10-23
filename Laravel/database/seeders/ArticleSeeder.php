<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get IDs from seeded data
        $storeId = DB::table('stores')->where('store_name', 'Amazon')->value('id');
        $categoryId = DB::table('categories')->where('category_name', 'Buoni Sconto')->value('id');

        DB::table('articles')->insert([
            [
                'sku' => 'AMZ-BUONO-15',
                'article_name' => 'Buono Amazon da 15€',
                'description' => 'Buono regalo digitale Amazon del valore di 15 euro. Utilizzabile per qualsiasi acquisto su Amazon.it',
                'pv_price' => 300,
                'is_digital' => true,
                'available' => true,
                'category_id' => $categoryId,
                'store_id' => $storeId,
                'thumbnail_asset_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sku' => 'AMZ-BUONO-30',
                'article_name' => 'Buono Amazon da 30€',
                'description' => 'Buono regalo digitale Amazon del valore di 30 euro. Utilizzabile per qualsiasi acquisto su Amazon.it',
                'pv_price' => 600,
                'is_digital' => true,
                'available' => true,
                'category_id' => $categoryId,
                'store_id' => $storeId,
                'thumbnail_asset_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sku' => 'AMZ-BUONO-50',
                'article_name' => 'Buono Amazon da 50€',
                'description' => 'Buono regalo digitale Amazon del valore di 50 euro. Utilizzabile per qualsiasi acquisto su Amazon.it',
                'pv_price' => 1000,
                'is_digital' => true,
                'available' => true,
                'category_id' => $categoryId,
                'store_id' => $storeId,
                'thumbnail_asset_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}