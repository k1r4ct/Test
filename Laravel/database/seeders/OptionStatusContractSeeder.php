<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OptionStatusContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //FASE "INSERIMENTO PROPOSTA" E MACRO STATO "CARICAMENTO CONTRATTO"
        DB::table('option_status_contracts')->insert([
          "macro_stato"=>"Caricamento Contratto",
          "fase"=>"Inserimento Proposta",
          "specifica"=>"Stato automatico quando si inserisce un contratto di un cliente",
          "genera_pv"=>0,
          "genera_pc"=>0,
          "status_contract_id"=>1,
          "applicabile_da_role_id"=>1,  
        ]);

        DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"Stato automatico quando si inserisce un contratto di un cliente",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>1,
            "applicabile_da_role_id"=>2,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"Stato automatico quando si inserisce un contratto di un cliente",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>1,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>2,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>2,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>3,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>3,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>4,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>4,
            "applicabile_da_role_id"=>2,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>4,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>5,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>5,
            "applicabile_da_role_id"=>2,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>5,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>6,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Caricamento Contratto",
            "fase"=>"Inserimento Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>6,
            "applicabile_da_role_id"=>5,  
          ]);

          //FASE "LAVORAZIONE PROPOSTA" E MACRO STATO "LAVORAZIONE CONTRATTO A SISTEMA"

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>7,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>7,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>8,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>8,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>9,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>9,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>10,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Lavorazione Contratto a Sistema",
            "fase"=>"Lavorazione Proposta",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>0,
            "status_contract_id"=>10,
            "applicabile_da_role_id"=>5,  
          ]);

          //FASE "AVANZAMENTO PRATICA" E MACRO STATO "CONTRATTO INSERITO PARTNER"

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>0,
            "status_contract_id"=>11,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>0,
            "status_contract_id"=>11,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>12,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>0,
            "genera_pc"=>0,
            "status_contract_id"=>12,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>0,
            "status_contract_id"=>13,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>0,
            "status_contract_id"=>13,
            "applicabile_da_role_id"=>5,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>0,
            "status_contract_id"=>14,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>0,
            "status_contract_id"=>14,
            "applicabile_da_role_id"=>5,  
          ]);

          //FASE "FASE FINALE" E MACRO STATO "ESITO AMMINISTRATIVO"

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>1,
            "status_contract_id"=>15,
            "applicabile_da_role_id"=>1,  
          ]);

          DB::table('option_status_contracts')->insert([
            "macro_stato"=>"Contratto Inserito Partner",
            "fase"=>"Avanzamento Pratica",
            "specifica"=>"",
            "genera_pv"=>1,
            "genera_pc"=>1,
            "status_contract_id"=>16,
            "applicabile_da_role_id"=>1,  
          ]);
    }
}
