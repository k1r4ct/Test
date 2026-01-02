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
        Schema::create('log_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Setting key identifier');
            $table->text('value')->nullable()->comment('Setting value (JSON for complex values)');
            $table->string('type', 20)->default('string')->comment('Value type: string, integer, boolean, json');
            $table->string('group', 50)->default('general')->comment('Setting group for UI organization');
            $table->string('label', 255)->nullable()->comment('Human readable label');
            $table->text('description')->nullable()->comment('Setting description');
            $table->timestamps();
            
            $table->index('group');
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_settings');
    }

    /**
     * Seed default log settings
     */
    private function seedDefaultSettings(): void
    {
        $now = now();
        
        $settings = [
            // RETENTION SETTINGS
            ['key' => 'retention_auth', 'value' => '30', 'type' => 'integer', 'group' => 'retention', 'label' => 'Auth (login/logout)', 'description' => 'Giorni di conservazione per i log di autenticazione'],
            ['key' => 'retention_api', 'value' => '14', 'type' => 'integer', 'group' => 'retention', 'label' => 'API', 'description' => 'Giorni di conservazione per i log API'],
            ['key' => 'retention_database', 'value' => '14', 'type' => 'integer', 'group' => 'retention', 'label' => 'Database', 'description' => 'Giorni di conservazione per i log database'],
            ['key' => 'retention_scheduler', 'value' => '14', 'type' => 'integer', 'group' => 'retention', 'label' => 'Scheduler', 'description' => 'Giorni di conservazione per i log scheduler'],
            ['key' => 'retention_email', 'value' => '30', 'type' => 'integer', 'group' => 'retention', 'label' => 'Email', 'description' => 'Giorni di conservazione per i log email'],
            ['key' => 'retention_system', 'value' => '30', 'type' => 'integer', 'group' => 'retention', 'label' => 'Sistema', 'description' => 'Giorni di conservazione per i log di sistema'],
            ['key' => 'retention_user_activity', 'value' => '60', 'type' => 'integer', 'group' => 'retention', 'label' => 'Attività Utente', 'description' => 'Giorni di conservazione per i log attività utente'],
            ['key' => 'retention_external_api', 'value' => '60', 'type' => 'integer', 'group' => 'retention', 'label' => 'External API', 'description' => 'Giorni di conservazione per i log API esterne'],
            ['key' => 'retention_errors', 'value' => '60', 'type' => 'integer', 'group' => 'retention', 'label' => 'Errori', 'description' => 'Giorni di conservazione per i log di errore'],

            // LOGGING OPTIONS
            ['key' => 'log_to_database', 'value' => 'true', 'type' => 'boolean', 'group' => 'options', 'label' => 'Scrivi log su database', 'description' => 'Salva i log nella tabella del database'],
            ['key' => 'log_to_file', 'value' => 'true', 'type' => 'boolean', 'group' => 'options', 'label' => 'Scrivi log su file', 'description' => 'Salva i log nei file di storage/logs'],
            ['key' => 'log_emails_auto', 'value' => 'true', 'type' => 'boolean', 'group' => 'options', 'label' => 'Log automatico email', 'description' => 'Registra automaticamente tutte le email inviate'],
            ['key' => 'log_slow_queries', 'value' => 'true', 'type' => 'boolean', 'group' => 'options', 'label' => 'Log query lente', 'description' => 'Registra le query che superano la soglia'],
            ['key' => 'slow_query_threshold', 'value' => '1000', 'type' => 'integer', 'group' => 'options', 'label' => 'Soglia query lente (ms)', 'description' => 'Tempo in millisecondi oltre il quale una query è considerata lenta'],
            ['key' => 'log_all_queries', 'value' => 'false', 'type' => 'boolean', 'group' => 'options', 'label' => 'Log tutte le query', 'description' => 'ATTENZIONE: Alto volume! Registra tutte le query'],

            // NOTIFICATIONS
            ['key' => 'notify_critical_errors', 'value' => 'true', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Email alert per errori critici', 'description' => 'Invia email quando si verificano errori critici'],
            ['key' => 'notify_email', 'value' => 'admin@semprechiaro.com', 'type' => 'string', 'group' => 'notifications', 'label' => 'Email destinatario', 'description' => 'Indirizzo email per le notifiche di errore'],

            // CLEANUP
            ['key' => 'cleanup_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'cleanup', 'label' => 'Esegui pulizia automatica', 'description' => 'Abilita la pulizia automatica dei log vecchi'],
            ['key' => 'cleanup_frequency', 'value' => 'daily', 'type' => 'string', 'group' => 'cleanup', 'label' => 'Frequenza', 'description' => 'Frequenza della pulizia: daily, weekly, monthly'],
            ['key' => 'cleanup_time', 'value' => '03:00', 'type' => 'string', 'group' => 'cleanup', 'label' => 'Ora esecuzione', 'description' => 'Ora di esecuzione della pulizia (formato HH:MM)'],
            ['key' => 'cleanup_last_run', 'value' => null, 'type' => 'string', 'group' => 'cleanup', 'label' => 'Ultima esecuzione', 'description' => 'Data e ora dell\'ultima pulizia eseguita'],
        ];

        foreach ($settings as $setting) {
            $setting['created_at'] = $now;
            $setting['updated_at'] = $now;
            DB::table('log_settings')->insert($setting);
        }
    }
};