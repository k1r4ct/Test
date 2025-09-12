<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TableColor extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('table_colors')->insert([
            "colore"=>"rgba(255, 0, 0, 0.576)",
        ]);
        DB::table('table_colors')->insert([
            "colore"=>"rgba(4, 220, 4, 0.546)",
        ]);

        DB::table('table_colors')->insert([
            "colore"=>"rgba(0, 255, 0, 0.667)",
        ]);

        DB::table('table_colors')->insert([
            "colore"=>"white",
        ]);
    }
}
