<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StatusContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('status_contracts')->insert([
            "micro_stato"=>"Proposta Inserita",//ID 1
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Da Correggere",//ID 2
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Proposta KO",//ID 3
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Proposta Re-inserita",//ID 4
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Proposta Annullata",//ID 5
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Proposta Caricata",//ID 6
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Contatto con il cliente OK",//ID 7
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Contatto con il cliente KO",//ID 8
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"KO inserimento a sistema",//ID 9
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Contratto Inserito a Sistema",//ID 10
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Contratto Sospeso",//ID 11
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Contratto KO",//ID 12
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Lavorazione Prolungata",//ID 13
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Contratto Attivo",//ID 14
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Gettonato",//ID 15
        ]);

        DB::table('status_contracts')->insert([
            "micro_stato"=>"Stornato",//ID 16
        ]);
    }
}
