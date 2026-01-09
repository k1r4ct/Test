<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds session inactivity timeout settings per role to the log_settings table.
     * Values are stored in SECONDS.
     * 
     * Default values:
     * - Admin/BackOffice: 3600 seconds (60 minutes)
     * - Advisor/Cliente/Operatore: 1200 seconds (20 minutes)
     */
    public function up(): void
    {
        $now = now();
        
        $settings = [
            [
                'key' => 'session_timeout_admin',
                'value' => '3600',
                'type' => 'integer',
                'group' => 'session',
                'label' => 'Administrator',
                'description' => 'Timeout inattività per Administrator (secondi)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'session_timeout_advisor',
                'value' => '1200',
                'type' => 'integer',
                'group' => 'session',
                'label' => 'Advisor (SEU)',
                'description' => 'Timeout inattività per Advisor/SEU (secondi)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'session_timeout_cliente',
                'value' => '1200',
                'type' => 'integer',
                'group' => 'session',
                'label' => 'Cliente',
                'description' => 'Timeout inattività per Cliente (secondi)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'session_timeout_operatore',
                'value' => '1200',
                'type' => 'integer',
                'group' => 'session',
                'label' => 'Operatore Web',
                'description' => 'Timeout inattività per Operatore Web (secondi)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'session_timeout_backoffice',
                'value' => '3600',
                'type' => 'integer',
                'group' => 'session',
                'label' => 'BackOffice',
                'description' => 'Timeout inattività per BackOffice (secondi)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($settings as $setting) {
            // Insert only if not exists
            $exists = DB::table('log_settings')->where('key', $setting['key'])->exists();
            if (!$exists) {
                DB::table('log_settings')->insert($setting);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('log_settings')->where('group', 'session')->delete();
    }
};