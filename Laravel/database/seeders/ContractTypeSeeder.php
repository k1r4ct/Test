<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ContractTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contract_type_informations')->insert([
            "macro_product_id"=>2,
            "domanda"=>"Numero linea",
            "tipo_risposta"=>"Text",
        ]);

        DB::table('contract_type_informations')->insert([
            "macro_product_id"=>2,
            "domanda"=>"Convergenza",
            "tipo_risposta"=>"Boolean",
        ]);

        DB::table('contract_type_informations')->insert([
            "macro_product_id"=>3,
            "domanda"=>"Seriale SIM",
            "tipo_risposta"=>"Text",
        ]);

        DB::table('contract_type_informations')->insert([
            "macro_product_id"=>3,
            "domanda"=>"Gestore attuale",
            "tipo_risposta"=>"Text",
        ]);

        DB::table('contract_type_informations')->insert([
            "macro_product_id"=>2,
            "domanda"=>"Tecnologia",
            "tipo_risposta"=>"Select",
        ]);
    }
}
