<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('leads')->insert([
            "invitato_da_user_id"=>1,
            "nome"=>"Alessio",
            "cognome"=>"Scion",
            "telefono"=>"3492881265",
            "email"=>"alessioscionti@gmail.com",
            "lead_status_id"=>1,
        ]);

        DB::table('leads')->insert([
            "invitato_da_user_id"=>1,
            "nome"=>"Antonio",
            "cognome"=>"Russotti",
            "telefono"=>"3490000000",
            "email"=>"a.russotti@gmail.com",
            "lead_status_id"=>2,
        ]);

        DB::table('leads')->insert([
            "invitato_da_user_id"=>1,
            "nome"=>"Lucio",
            "cognome"=>"Calanna",
            "telefono"=>"3490000000",
            "email"=>"l.c@gmail.com",
            "lead_status_id"=>3,
        ]);

        DB::table('leads')->insert([
            "invitato_da_user_id"=>1,
            "nome"=>"Kevini",
            "cognome"=>"Ciaramitaro",
            "telefono"=>"3490000000",
            "email"=>"k.c@gmail.com",
            "lead_status_id"=>4,
        ]);

        DB::table('leads')->insert([
            "invitato_da_user_id"=>1,
            "nome"=>"Jason",
            "cognome"=>"Pappalardo",
            "telefono"=>"3490000000",
            "email"=>"j.p@gmail.com",
            "lead_status_id"=>5,
        ]);

        DB::table('leads')->insert([
            "invitato_da_user_id"=>1,
            "nome"=>"Saro",
            "cognome"=>"Falsaperla",
            "telefono"=>"3490000000",
            "email"=>"s.f@gmail.com",
            "lead_status_id"=>6,
        ]);
    }
}
