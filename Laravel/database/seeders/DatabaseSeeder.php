<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,//COMPILATA
            QualificationSeeder::class,//COMPILATA
            UserSeeder::class,//COMPILATA
            LeadStatusesSeeder::class,//COMPILATA
            LeadSeeder::class,//COMPILATA
            IndirectsSeeder::class,//COMPILATA
            SurveySeeder::class,//NON COMPILATA AL MOMENTO
            CustomerDataSeeder::class,//COMPILATA
            StatusContractSeeder::class,//COMPILATA
            OptionStatusContractSeeder::class,//COMPILATA
            SupplierCategoriesSeeder::class,//COMPILATA
            SupplierSeeder::class,//COMPILATA
            MacroProductSeeder::class,//COMPILATA
            ProductSeeder::class,//COMPILATA IN PARTE
            SpecificDataSeeder::class,//COMPILATA
            ContractTypeSeeder::class,//NON COMPILATA AL MOMENTO
            DetailQuestion::class,//COMPILATA
            PaymentModeSeeder::class,//NON COMPILATA AL MOMENTO
            DocumentsDataSeeder::class,//NON COMPILATA AL MOMENTO
            ContractSeeder::class,//NON COMPILATA AL MOMENTO
            SurveyTypeSeeder::class,//NON COMPILATA AL MOMENTO
            contract_management::class,//COMPILATA
            
        ]);
    }
}
