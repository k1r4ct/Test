<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CartStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cart_statuses')->insert([
            ['status_name' => 'attivo', 'created_at' => now(), 'updated_at' => now()],
            ['status_name' => 'in_attesa_di_pagamento', 'created_at' => now(), 'updated_at' => now()],
            ['status_name' => 'pagamento_effettuato', 'created_at' => now(), 'updated_at' => now()],
            ['status_name' => 'completato', 'created_at' => now(), 'updated_at' => now()],
            ['status_name' => 'inattivo', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}