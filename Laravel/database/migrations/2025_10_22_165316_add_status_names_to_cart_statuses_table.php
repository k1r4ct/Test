<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the enum to add new values
        DB::statement("ALTER TABLE cart_statuses MODIFY COLUMN status_name ENUM('attivo', 'in_attesa_di_pagamento', 'pagamento_effettuato', 'completato', 'inattivo')");
    }

    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE cart_statuses MODIFY COLUMN status_name ENUM('attivo', 'in_attesa_di_pagamento', 'completato')");
    }
};