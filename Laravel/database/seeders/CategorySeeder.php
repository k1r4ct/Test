<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'category_name' => 'Buoni Sconto',
                'description' => 'Buoni sconto digitali per acquisti online',
                'filter_id' => null, // Visible to all users
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}