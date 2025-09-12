<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SupplierCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('supplier_categories')->insert([
            "nome_categoria"=>"Energia",
            "descrizione"=>"",
        ]);

        DB::table('supplier_categories')->insert([
            "nome_categoria"=>"Telefonia",
            "descrizione"=>"",
        ]);

        DB::table('supplier_categories')->insert([
            "nome_categoria"=>"Consulenza",
            "descrizione"=>"",
        ]);

        DB::table('supplier_categories')->insert([
            "nome_categoria"=>"Assicurazione",
            "descrizione"=>"",
        ]);

        DB::table('supplier_categories')->insert([
            "nome_categoria"=>"Fotovoltaico",
            "descrizione"=>"",
        ]);

        DB::table('supplier_categories')->insert([
            "nome_categoria"=>"Finanze",
            "descrizione"=>"",
        ]);

        DB::table('supplier_categories')->insert([
            "nome_categoria"=>"Tv",
            "descrizione"=>"",
        ]);
    }
}
