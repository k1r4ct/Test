<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CustomerDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customer_datas')->insert([
            "nome"=>"Jhonatah",
            "cognome"=>"Pappalardo",
            "email"=>"j.p@gmail.com",
            "pec"=>"",
            "codice_fiscale"=>"JNTPPL88N30C351N",
            "telefono"=>"3490000000",
            "citta"=>"Catania",
            "cap"=>"95121",
            "provincia"=>"CT",
            "nazione"=>"ITALIA",
        ]);
    }
}
