<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class QualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('qualifications')->insert([
            "descrizione"=>"Director",
            "pc_necessari"=>80000,
            "compenso_pvdiretti"=>"50",
            "pc_bonus_mensile"=>25000,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"Area Manager",
            "pc_necessari"=>50000,
            "compenso_pvdiretti"=>"50",
            "pc_bonus_mensile"=>18000,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"District Manager",
            "pc_necessari"=>30000,
            "compenso_pvdiretti"=>"48",
            "pc_bonus_mensile"=>12000,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"Manager",
            "pc_necessari"=>13000,
            "compenso_pvdiretti"=>"45",
            "pc_bonus_mensile"=>8000,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"Utility Manager Senor (UMS)",
            "pc_necessari"=>6000,
            "compenso_pvdiretti"=>"35",
            "pc_bonus_mensile"=>3500,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"Utility Manager Junior (UMJ)",
            "pc_necessari"=>0,
            "compenso_pvdiretti"=>"25",
            "pc_bonus_mensile"=>3000,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"Referal director / Affiliato",
            "pc_necessari"=>0,
            "compenso_pvdiretti"=>"10",
            "pc_bonus_mensile"=>0,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"Segnalatore",
            "pc_necessari"=>0,
            "compenso_pvdiretti"=>"8",
            "pc_bonus_mensile"=>0,
        ]);

        DB::table('qualifications')->insert([
            "descrizione"=>"Cliente",
            "pc_necessari"=>0,
            "compenso_pvdiretti"=>"0",
            "pc_bonus_mensile"=>0,
        ]);
    }
}
