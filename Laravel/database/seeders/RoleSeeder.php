<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            "descrizione"=>"Administrator",
        ]);

        DB::table('roles')->insert([
            "descrizione"=>"Advisor",
        ]);

        DB::table('roles')->insert([
            "descrizione"=>"Cliente",
        ]);

        DB::table('roles')->insert([
            "descrizione"=>"Operatore Web",
        ]);

        DB::table('roles')->insert([
            "descrizione"=>"BackOffice",
        ]);
    }
}
