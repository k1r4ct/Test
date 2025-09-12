<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DetailQuestion extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('detail_questions')->insert([
            'contract_type_information_id'=>5,
            'opzione'=>"Adsl"
        ]);
        DB::table('detail_questions')->insert([
            'contract_type_information_id'=>5,
            'opzione'=>"FTTC"
        ]);
        DB::table('detail_questions')->insert([
            'contract_type_information_id'=>5,
            'opzione'=>"FTTH"
        ]);
        DB::table('detail_questions')->insert([
            'contract_type_information_id'=>5,
            'opzione'=>"Rame"
        ]);
        DB::table('detail_questions')->insert([
            'contract_type_information_id'=>5,
            'opzione'=>"Mista"
        ]);
    }
}
