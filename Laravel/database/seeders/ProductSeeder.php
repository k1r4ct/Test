<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            "descrizione" => "RESIDENZIALE LUCE/GAS",
            "supplier_id" => 1,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 57.24,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "RESIDENZIALE LUCE/GAS RID/CC",
            "supplier_id" => 1,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 81.09,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "RESIDENZIALE LUCE/GAS + VAS +RID",
            "supplier_id" => 1,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 81.09,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO PREMIUM LUCE/GAS CON RID/CC",
            "supplier_id" => 1,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 152.64,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO PREMIUM LUCE/GAS CON RID/CC + VAS",
            "supplier_id" => 1,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 171.72,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO TOP LUCE/GAS CON RID/CC",
            "supplier_id" => 1,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 143.1,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO TOP LUCE/GAS CON RID/CC + VAS",
            "supplier_id" => 1,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 143.1,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL CONSUMER LUCE",
            "supplier_id" => 2,
            "punti_valore" => 50,
            "punti_carriera" => 50,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 61.06,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL CONSUMER GAS",
            "supplier_id" => 2,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 75.37,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL MICRO 1<4 KW",
            "supplier_id" => 2,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 75.37,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL MICRO 4<12 KW",
            "supplier_id" => 2,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 100.17,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL MICRO 12<50 KW",
            "supplier_id" => 2,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 133.56,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL MICRO >50",
            "supplier_id" => 2,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 200.34,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL Enterprice",
            "supplier_id" => 2,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 7,
            "gettone" => 341.05,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENEL MICRO GAS",
            "supplier_id" => 2,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 95.4,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "LUCE-GAS CONSUMER",
            "supplier_id" => 3,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 57.24,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "LUCE-GAS MICRO",
            "supplier_id" => 3,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 76.32,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "HERACOM ENTERPRICE",
            "supplier_id" => 3,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 7,
            "gettone" => 219.42,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENERGIA Luce/Gas CNS Mercato libero",
            "supplier_id" => 4,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 76.32,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "ENERGIA Luce/Gas CNS Mercato vincolato",
            "supplier_id" => 4,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 76.32,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSUMER LUCE-GAS 0-PROBLEMI",
            "supplier_id" => 5,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 72.5,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSUMER LUCE-GAS 3IN1 CON SDD",
            "supplier_id" => 5,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 71.55,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSUMER LUCE-GAS 3IN1 SENZA SDD",
            "supplier_id" => 5,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 47.7,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO < 5,1 KW",
            "supplier_id" => 5,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 101.12,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO  5<10,1 KW",
            "supplier_id" => 5,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 112.6,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO  >10 KW",
            "supplier_id" => 5,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 66.78,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO  GAS",
            "supplier_id" => 5,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 82.04,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FASTWEB NEXXT LIGHT",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 114.48,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FASTWEB FWA",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 124.02,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FASTWEB NEXXT CASA",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 133.56,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FASTWEB NEXXT CASA PLUS",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 171.72,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE NEXXT",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 35.77,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE NEXXT LIGHT",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 16.69,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE NEXXT MAXI",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 59.62,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE DATI 100 GB",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 29.57,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO BUSINESS",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 219.42,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO BUSINESS+CHIAMATE+BACKUP",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 238.5,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "My Business CLASS P.IVA X2",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 171.72,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO BUSINESS NEXXT LIGHT",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 95.4,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE MICRO NEXXT FREDOM",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 38.16,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE MICRO NEXXT BUSINESS",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 19.08,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "SMALL ULLIMITED 2-3 LINEE",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 8,
            "gettone" => 381.6,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "SMALL ULLIMITED 4-5 LINEE",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 8,
            "gettone" => 524.7,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "SMALL ULLIMITED 6-8 LINEE",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 8,
            "gettone" => 664.8,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONNETTIVITA TOP (50-100)",
            "supplier_id" => 6,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 8,
            "gettone" => 381.6,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSUMER NUOVE ATTIVAZIONI – NIP/ULL",
            "supplier_id" => 7,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 128.79,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO FONIA FISSA",
            "supplier_id" => 7,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 147.87,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MICRO FONIA FISSA CONVERGENZA",
            "supplier_id" => 7,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 179.35,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE MICRO OFF >10€",
            "supplier_id" => 7,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 51.039,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE MICRO OFF >15€",
            "supplier_id" => 7,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 57.717,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "MOBILE MICRO OFF >15€ IN CONVERGENZA",
            "supplier_id" => 7,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 70.596,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 7€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 30.051,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 10€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 42.93,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 12€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 51.516,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 15€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 64.395,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 6€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 25.758,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 8€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 34.344,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 9€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 38.636,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 17€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 72.981,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.- Canone 30€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 128.79,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.-CB MIA DA 6€-10€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 13.356,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "C.I.-CB MIA 11€-20€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 24.804,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "S.I.-Canone 7€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 20.034,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "S.I.-Canone 10€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 28.62,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "S.I.-Canone 15€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 42.93,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "S.I.-Canone 6€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 17.172,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "S.I.-Canone 12€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 34.344,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "S.I.-Canone 17€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 48.654,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "S.I.-Canone 30€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 85.86,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "PARTITA IVA C.I.- Canone 8€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 41.976,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "PARTITA IVA C.I.- Canone 10€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 52.47,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "PARTITA IVA C.I.- Canone 15€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 78.705,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "PARTITA IVA C.I.- Canone 20€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 4,
            "gettone" => 104.94,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA NUOVA LINEA",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 41.976,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA IN MNP",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 62.964,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA NUOVA LINEA CON RID/CC",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 52.47,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA IN MNP CON RID/CC",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 73.458,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA NUOVA LINEA Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 83.952,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA IN MNP Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 104.94,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA NUOVA LINEA CON RID/CC Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 94.446,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "FONIA FISSA IN MNP CON RID/CC Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 115.434,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA NUOVA LINEA",
            "supplier_id" => 8,
            "punti_valore" => 180,
            "punti_carriera" => 200,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 62.964,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA IN MNP",
            "supplier_id" => 8,
            "punti_valore" => 180,
            "punti_carriera" => 200,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 83.952,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA NUOVA LINEA CON RID/CC",
            "supplier_id" => 8,
            "punti_valore" => 180,
            "punti_carriera" => 200,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 73.458,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA IN MNP CON RID/CC",
            "supplier_id" => 8,
            "punti_valore" => 180,
            "punti_carriera" => 200,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 94.446,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA NUOVA LINEA Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 180,
            "punti_carriera" => 200,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 104.94,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA IN MNP Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 180,
            "punti_carriera" => 200,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 125.928,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA NUOVA LINEA CON RID/CC Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 115.434,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "P.IVA- FONIA FISSA IN MNP CON RID/CC Con convegenza Contestuale",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 136.422,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CB FONIA FISSA Canone inferiore a 27€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 62.964,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CB FONIA FISSA con P.IVA Canone inferiore a 27€",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 2,
            "gettone" => 104.94,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "WINDTRE FWA",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 76.32,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "WINDTRE FWA IN CONVERGENZA",
            "supplier_id" => 8,
            "punti_valore" => 100,
            "punti_carriera" => 100,
            "attivo" => 1,
            "macro_product_id" => 1,
            "gettone" => 95.4,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "OFFICE SMART SMALL ( 2 INTERNI )",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 8,
            "gettone" => 171.72,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "OFFICE SMALRT LARGE (FINO A 11 INTERNI)",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 8,
            "gettone" => 381.6,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "OFFICE SMART EXTRALARGE (FINO A 25 INTERNI)",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 8,
            "gettone" => 810.9,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "SIM HO (NO PORTABILITA DA VODAFONE)",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 20.988,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "SIM HO PORTABILITA DA ALTRI OPERATORI VIRTUALI",
            "supplier_id" => 8,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 3,
            "gettone" => 32.436,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSULENZA VALUE",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 10,
            "gettone" => 367,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSULENZA PRO",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 11,
            "gettone" => 867,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSULENZA PRO X",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 12,
            "gettone" => 1437,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSULENZA FAMILY",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 15,
            "gettone" => 167,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "CONSULENZA PRO X ASSICURAZIONE",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 16,
            "gettone" => 167,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "SERVIZIO GESTIONE AUTOLETTURA GAS",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 17,
            "gettone" => 167,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);


        DB::table('products')->insert([
            "descrizione" => "IMPIANTO FOTOVOLTAICO",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 13,
            "gettone" => 954,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "PUNTO FORNITURA AGGIUNTIVO",
            "supplier_id" => 9,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 14,
            "gettone" => 954,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);


        DB::table('products')->insert([
            "descrizione" => "EVISO ENERGIA CNS Family",
            "supplier_id" => 10,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 76.32,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "EVISO ENERGIA Micro Business",
            "supplier_id" => 10,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 114.48,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "EVISO ENERGIA Business",
            "supplier_id" => 10,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 143.1,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "EVISO ENERGIA Corporate",
            "supplier_id" => 10,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 76.32,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "LUCE/GAS RESIDENZIALE",
            "supplier_id" => 11,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 74.412,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "LUCE/GAS MICRO FINO A 15000kw/5000smc",
            "supplier_id" => 11,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 114.48,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "LUCE/GAS MICRO FINO da 15<50000 kw/5<15000smc",
            "supplier_id" => 11,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 131.652,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "LUCE/GAS MICRO FINO da 50<100000 kw/15<30000smc",
            "supplier_id" => 11,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 150.732,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "Sky-Stream Hospitality",
            "supplier_id" => 12,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 11,
            "gettone" => 436.932,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "E-Like Residenziale",
            "supplier_id" => 13,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 5,
            "gettone" => 150.732,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "Micro fino a 6,6 KW",
            "supplier_id" => 13,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 150.732,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "Micro >7<10",
            "supplier_id" => 13,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 150.732,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "Micro >10<30",
            "supplier_id" => 13,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 150.732,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

        DB::table('products')->insert([
            "descrizione" => "Micro >30<100",
            "supplier_id" => 13,
            "punti_valore" => 0,
            "punti_carriera" => 0,
            "attivo" => 1,
            "macro_product_id" => 6,
            "gettone" => 150.732,
            "inizio_offerta" => "",
            "fine_offerta" => "",
        ]);

    }
}
