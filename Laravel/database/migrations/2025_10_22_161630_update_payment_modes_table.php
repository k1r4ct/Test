<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add payment_type column
        Schema::table('payment_modes', function (Blueprint $table) {
            $table->enum('payment_type', ['instant', 'electronic', 'manual'])
                  ->default('electronic')
                  ->after('tipo_pagamento');
        });

        // Add "Punti Valore" as instant payment method
        DB::table('payment_modes')->insert([
            'tipo_pagamento' => 'Punti Valore',
            'payment_type' => 'instant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove "Punti Valore"
        DB::table('payment_modes')->where('tipo_pagamento', 'Punti Valore')->delete();
        
        // Remove column
        Schema::table('payment_modes', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};