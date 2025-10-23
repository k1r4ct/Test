<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('order_statuses')->insert([
            [
                'status_name' => 'inserito',
                'description' => 'Ordine inserito, in attesa di elaborazione',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'status_name' => 'attesa_pagamento',
                'description' => 'In attesa di conferma pagamento esterno',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'status_name' => 'in_elaborazione',
                'description' => 'Ordine in elaborazione, ticket creato per backoffice',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'status_name' => 'ordine_spedito',
                'description' => 'Ordine spedito (solo per store fisici)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'status_name' => 'ordine_evaso',
                'description' => 'Ordine completato ed evaso',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'status_name' => 'ordine_annullato',
                'description' => 'Ordine annullato',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}