<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MacroProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC10",
            "descrizione"=>"CONTRATTO TELECOMUNICAZIONI CONSUMER",
            "punti_valore"=>100,
            "punti_carriera"=>100,
            "supplier_category_id"=>2,
        ]);

        // 2
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC11",
            "descrizione"=>"CONTRATTO TELECOMUNICAZIONI MICRO",
            "punti_valore"=>180,
            "punti_carriera"=>200,
            "supplier_category_id"=>2,
        ]);

        // 3
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC12",
            "descrizione"=>"CONTRATTO MOBILE CONSUMER",
            "punti_valore"=>25,
            "punti_carriera"=>30,
            "supplier_category_id"=>2,
        ]);

        // 4
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC13",
            "descrizione"=>"CONTRATTO MOBILE MICRO",
            "punti_valore"=>37,
            "punti_carriera"=>40,
            "supplier_category_id"=>2,
        ]);

        // 5
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC14",
            "descrizione"=>"CONTRATTO ENERGIA LUCE/GAS CONSUMER",
            "punti_valore"=>50,
            "punti_carriera"=>50,
            "supplier_category_id"=>1,
        ]);

        // 6
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC15",
            "descrizione"=>"CONTRATTO ENERGIA LUCE/GAS MICRO",
            "punti_valore"=>100,
            "punti_carriera"=>100,
            "supplier_category_id"=>1,
        ]);

        // 7
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC16",
            "descrizione"=>"CONTRATTO ENERGIA MICRO CORPORATE",
            "punti_valore"=>350,
            "punti_carriera"=>500,
            "supplier_category_id"=>1,
        ]);

        // 8
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC17",
            "descrizione"=>"CONTRATTO TELECOMUNICAZIONI MICRO SME",
            "punti_valore"=>400,
            "punti_carriera"=>500,
            "supplier_category_id"=>2,
        ]);

        // 9
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC20",
            "descrizione"=>"SERVIZIO TV BUSINESS",
            "punti_valore"=>400,
            "punti_carriera"=>400,
            "supplier_category_id"=>7,
        ]);

        // 10
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC24",
            "descrizione"=>"CONSULENZA VALUE",
            "punti_valore"=>350,
            "punti_carriera"=>350,
            "supplier_category_id"=>3,
        ]);

        // 11
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC25",
            "descrizione"=>"CONSULENZA PRO",
            "punti_valore"=>800,
            "punti_carriera"=>800,
            "supplier_category_id"=>3,
        ]);

        // 12
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC26",
            "descrizione"=>"CONSULENZA PRO X",
            "punti_valore"=>1450,
            "punti_carriera"=>1500,
            "supplier_category_id"=>3,
        ]);

        // 13
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC23",
            "descrizione"=>"IMPIANTO FOTOVOLTAICO",
            "punti_valore"=>800,
            "punti_carriera"=>800,
            "supplier_category_id"=>5,
        ]);

        // 14
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC33",
            "descrizione"=>"PUNTO FORNITURA AGGIUNTIVO",
            "punti_valore"=>130,
            "punti_carriera"=>130,
            "supplier_category_id"=>3,
        ]);

        // 15
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC27",
            "descrizione"=>"CONSULENZA FAMILY",
            "punti_valore"=>150,
            "punti_carriera"=>150,
            "supplier_category_id"=>3,
        ]);

        // 16
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC28",
            "descrizione"=>"CONSULENZA PRO X ASSICURAZIONE",
            "punti_valore"=>1650,
            "punti_carriera"=>1800,
            "supplier_category_id"=>3,
        ]);

        // 17
        DB::table('macro_products')->insert([
            "codice_macro"=>"SC34",
            "descrizione"=>"SERVIZIO GESTIONE AUTOLETTURA GAS",
            "punti_valore"=>190,
            "punti_carriera"=>190,
            "supplier_category_id"=>3,
        ]);

    }
}
