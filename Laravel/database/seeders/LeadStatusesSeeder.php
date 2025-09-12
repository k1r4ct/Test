<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LeadStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //LAVORAZIONE LEAD INSERITO LAVORABILE DAI SEGUENTI USER
        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>3,
            "micro_stato"=>"Lead inserito",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"Stato automatico quando si inserisce un lead",
            "color_id"=>4,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>2,
            "micro_stato"=>"Lead inserito",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"Stato automatico quando si inserisce un lead",
            "color_id"=>4,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>5,
            "micro_stato"=>"Lead inserito",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"Stato automatico quando si inserisce un lead",
            "color_id"=>4,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>1,
            "micro_stato"=>"Lead inserito",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"Stato automatico quando si inserisce un lead",
            "color_id"=>4,
        ]);

        //LAVORAZIONE LEAD SOSPESO LAVORABILE DAI SEGUENTI USER

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>2,
            "micro_stato"=>"Lead Sospeso",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>4,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>5,
            "micro_stato"=>"Lead Sospeso",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>4,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>1,
            "micro_stato"=>"Lead Sospeso",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>4,
        ]);

        //LAVORAZIONE LEAD NON INTERESSATO LAVORABILE DAI SEGUENTI USER

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>2,
            "micro_stato"=>"Lead Non Interessato",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>1,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>5,
            "micro_stato"=>"Lead Non Interessato",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>1,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>1,
            "micro_stato"=>"Lead Non Interessato",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>1,
        ]);

        //LAVORAZIONE APPUNTAMENTO PRESO LAVORABILE DAI SEGUENTI USER

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>2,
            "micro_stato"=>"Appuntamento Preso",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>2,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>5,
            "micro_stato"=>"Appuntamento Preso",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>2,

        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>1,
            "micro_stato"=>"Appuntamento Preso",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>2,

        ]);

        //LAVORAZIONE APPUNTAMENTO KO LAVORABILE DAI SEGUENTI USER

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>2,
            "micro_stato"=>"Appuntamento KO",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>1,

        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>5,
            "micro_stato"=>"Appuntamento KO",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>1,

        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>1,
            "micro_stato"=>"Appuntamento KO",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"",
            "color_id"=>1,

        ]);

        //LAVORAZIONE LEAD OK LAVORABILE DAI SEGUENTI USER

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>2,
            "micro_stato"=>"Lead OK",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"Solo quanto è in questo stato si puo convertire il Lead in Cliente",
            "color_id"=>3,

        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>5,
            "micro_stato"=>"Lead OK",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"Solo quanto è in questo stato si puo convertire il Lead in Cliente",
            "color_id"=>3,
        ]);

        DB::table('lead_statuses')->insert([
            "applicabile_da_role_id"=>1,
            "micro_stato"=>"Lead OK",
            "macro_stato"=>"Inserimento Lead",
            "fase"=>"Inserimento Lead",
            "specifica"=>"Solo quanto è in questo stato si puo convertire il Lead in Cliente",
            "color_id"=>3,

        ]);
    }
}
