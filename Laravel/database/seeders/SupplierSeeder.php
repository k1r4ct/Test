<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //1
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Edison",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //2
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Enel",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //3
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Heracom",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //4
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"A2A ENERGIA",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //5
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Engie",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //6
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Fastweb",
            "descrizione"=>"",
            "supplier_category_id"=>2
        ]);

        //7
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Tim",
            "descrizione"=>"",
            "supplier_category_id"=>2
        ]);

        //8
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Wind Tre",
            "descrizione"=>"",
            "supplier_category_id"=>2
        ]);

        //9
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Semprechiaro",
            "descrizione"=>"",
            "supplier_category_id"=>3
        ]);

        //10
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Eviso Energia",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //11
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Estra Energie",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //12
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Sky Business",
            "descrizione"=>"",
            "supplier_category_id"=>7
        ]);

        //13
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"E-LIKE",
            "descrizione"=>"",
            "supplier_category_id"=>1
        ]);

        //14
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"FASTWEB SME",
            "descrizione"=>"",
            "supplier_category_id"=>2
        ]);

        //15
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Ho Mobile",
            "descrizione"=>"",
            "supplier_category_id"=>2
        ]);

        //16
        DB::table('suppliers')->insert([
            "nome_fornitore"=>"Consulenza Semprechiaro",
            "descrizione"=>"",
            "supplier_category_id"=>3
        ]);

    }
}
