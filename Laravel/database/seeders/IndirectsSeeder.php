<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IndirectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //GESTIONE INDIRETTE PER DIRECTOR
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"4",
            "qualification_id"=>1
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"3",
            "qualification_id"=>1
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"1",
            "qualification_id"=>1
        ]);

        //GESTIONE INDIRETTE PER AREA MANAGER
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"4",
            "qualification_id"=>2
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"3",
            "qualification_id"=>2
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"1",
            "qualification_id"=>2
        ]);

        //GESTIONE INDIRETTE PER DISTRICT MANAGER
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"4",
            "qualification_id"=>3
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"3",
            "qualification_id"=>3
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"1",
            "qualification_id"=>3
        ]);

        //GESTIONE INDIRETTE PER MANAGER
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"4",
            "qualification_id"=>4
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"3",
            "qualification_id"=>4
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>4
        ]);

        //GESTIONE INDIRETTE PER UMS
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"4",
            "qualification_id"=>5
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>5
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>5
        ]);

        //GESTIONE INDIRETTE PER UMJ
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>6
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>6
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>6
        ]);

        //GESTIONE INDIRETTE PER REFEREAL DIRECTOR/AFFILIATO
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>7
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>7
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>7
        ]);

        //GESTIONE INDIRETTE PER SEGNALATORE
        DB::table('indirects')->insert([
            "numero_livello"=>1,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>8
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>2,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>8
        ]);

        DB::table('indirects')->insert([
            "numero_livello"=>3,
            "percentuale_indiretta"=>"0",
            "qualification_id"=>8
        ]);
    }
}
