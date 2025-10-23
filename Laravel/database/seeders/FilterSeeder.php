<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * NOTE: Update role_id and qualification_id based on your database
     */
    public function run(): void
    {
        DB::table('filters')->insert([
            // 1. Base filter: All users
            [
                'filter_name' => 'Tutti gli utenti',
                'description' => 'Visibile a tutti gli utenti registrati',
                'role_id' => null,
                'qualification_id' => null,
                'expand_to_leads' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 2. Solo Advisor (SEU) - tutte le qualifiche
            [
                'filter_name' => 'Solo Advisor',
                'description' => 'Visibile solo agli Advisor (SEU)',
                'role_id' => 2, // Advisor
                'qualification_id' => null,
                'expand_to_leads' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 3. Solo Clienti
            [
                'filter_name' => 'Solo Clienti',
                'description' => 'Visibile solo ai Clienti',
                'role_id' => 3, // Cliente
                'qualification_id' => null,
                'expand_to_leads' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 4. Advisor Senior (UMS e superiori)
            [
                'filter_name' => 'Advisor Senior',
                'description' => 'Visibile agli Advisor con qualification Utility Manager Senior o superiore',
                'role_id' => 2, // Advisor
                'qualification_id' => 5, // Utility Manager Senior
                'expand_to_leads' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 5. Advisor con expand_to_leads
            [
                'filter_name' => 'Advisor + Lead Convertiti',
                'description' => 'Visibile agli Advisor e ai loro lead convertiti',
                'role_id' => 2, // Advisor
                'qualification_id' => null,
                'expand_to_leads' => true, // I lead convertiti vedono con filtri inviter
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}