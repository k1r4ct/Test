<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('stores')->insert([
            [
                'store_name' => 'Amazon_Digital',
                'store_type' => 'digital',
                'filter_id' => null, // Visible to all users
                'active' => true,
            ],
        ]);
    }
}