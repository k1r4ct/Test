<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            "role_id"=>1,
            "qualification_id"=>1,
            "name"=>"Alessio",
            "cognome"=>"Scionti",
            "email"=>"as@gmail.com",
            "password"=>'$2y$12$db9i0eEl0cHRtC9k4aNXaOYOAZjgtyF6O3r0pE.TauwJsuey6kMwa',
            "codice_fiscale"=>"SCNLSS88L29C351N",
        ]);

        DB::table('users')->insert([
            "role_id"=>1,
            "qualification_id"=>1,
            "name"=>"Antonio",
            "cognome"=>"Russotti",
            "email"=>"a.russotti@gmail.com",
            "password"=>'$2y$12$db9i0eEl0cHRtC9k4aNXaOYOAZjgtyF6O3r0pE.TauwJsuey6kMwa',
            "codice_fiscale"=>"RSSNTN80D17C351G",
        ]);

        DB::table('users')->insert([
            "role_id"=>1,
            "qualification_id"=>1,
            "name"=>"Lucio",
            "cognome"=>"Calanna",
            "email"=>"l.calanna@elevasm.it",
            "password"=>'$2y$12$db9i0eEl0cHRtC9k4aNXaOYOAZjgtyF6O3r0pE.TauwJsuey6kMwa',
            "codice_fiscale"=>"CLNLCU84A26C351X",
            "telefono"=>"0952161070",
            "cellulare"=>"0952161070",
            "indirizzo"=>"Via ottava strada",
            "citta"=>"Catania",
            "provincia"=>"Ct",
            "nazione"=>"It",
            "codice"=>"PC0001",
        ]);

        DB::table('users')->insert([
            "role_id"=>5,
            "qualification_id"=>2,
            "name"=>"Davide",
            "cognome"=>"Arcifa",
            "email"=>"amministrazione@elevasm.it",
            "password"=>'$2y$12$db9i0eEl0cHRtC9k4aNXaOYOAZjgtyF6O3r0pE.TauwJsuey6kMwa',
            "codice_fiscale"=>"RCFDDS75D17C351I",
            "telefono"=>"0952161070",
            "cellulare"=>"3403429013",
            "indirizzo"=>"Via ottava strada",
            "citta"=>"Catania",
            "provincia"=>"Ct",
            "nazione"=>"It",
            "codice"=>"PC0002",
        ]);

        DB::table('users')->insert([
            "role_id"=>5,
            "qualification_id"=>2,
            "name"=>"Filippo",
            "cognome"=>"Sciacca",
            "email"=>"filipposciacca81@gmail.com",
            "password"=>'$2y$12$db9i0eEl0cHRtC9k4aNXaOYOAZjgtyF6O3r0pE.TauwJsuey6kMwa',
            "codice_fiscale"=>"SCCFPP81T13C351U",
            "telefono"=>"095514403",
            "cellulare"=>"3498457878",
            "indirizzo"=>"Via Madonna della via N 5",
            "citta"=>"Gravina di Catania",
            "provincia"=>"Ct",
            "nazione"=>"It",
            "codice"=>"PC0003",
        ]);

        DB::table('users')->insert([
            "role_id"=>5,
            "qualification_id"=>2,
            "name"=>"Maria",
            "cognome"=>"Di Mauro",
            "email"=>"marydimauro08@gmail.com",
            "password"=>'$2y$12$db9i0eEl0cHRtC9k4aNXaOYOAZjgtyF6O3r0pE.TauwJsuey6kMwa',
            "codice_fiscale"=>"DMRMRA84S48C351P",
            "telefono"=>"",
            "cellulare"=>"3299340265",
            "indirizzo"=>"VIA SANTA CATERINA 25",
            "citta"=>"San Pietro Clarenza",
            "provincia"=>"Ct",
            "nazione"=>"It",
            "codice"=>"PC0004",
        ]);

        DB::table('users')->insert([
            "role_id"=>2,
            "qualification_id"=>2,
            "name"=>"Adriano",
            "cognome"=>"Savettieri",
            "email"=>"adrianosavettieri@gmail.com",
            "password"=>'$2y$12$db9i0eEl0cHRtC9k4aNXaOYOAZjgtyF6O3r0pE.TauwJsuey6kMwa',
            "codice_fiscale"=>"SVTDRN82E10G273Y",
            "telefono"=>"0919775570",
            "cellulare"=>"3770854392",
            "indirizzo"=>"Via Giuseppe Vaccari 50",
            "citta"=>"Palermo",
            "provincia"=>"Pa",
            "nazione"=>"It",
            "codice"=>"PC0005",
        ]);
    }
}
