<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get IDs from seeded data
        $storeId = DB::table('stores')->where('store_name', 'Amazon')->value('id');
        
        $articles = DB::table('articles')
            ->whereIn('sku', ['AMZ-BUONO-15', 'AMZ-BUONO-30', 'AMZ-BUONO-50'])
            ->get();

        foreach ($articles as $article) {
            DB::table('stock')->insert([
                'article_id' => $article->id,
                'store_id' => $storeId,
                'quantity' => 9999, // Infinite stock for digital products
                'total_stock' => 9999,
                'minimum_stock' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}