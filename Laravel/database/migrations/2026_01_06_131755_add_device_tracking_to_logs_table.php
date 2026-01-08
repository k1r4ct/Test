<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add advanced device tracking fields to logs table
 * 
 * This migration adds comprehensive device fingerprinting and geolocation
 * fields to enable detailed user session tracking and security analysis.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            // ==================== DEVICE FINGERPRINT ====================
            $table->string('device_fingerprint', 64)->nullable()->after('user_agent')
                  ->comment('Unique device identifier hash');
            
            // ==================== GEOLOCATION (from IP) ====================
            $table->string('geo_country', 100)->nullable()->after('device_fingerprint')
                  ->comment('Country name from IP geolocation');
            $table->string('geo_country_code', 5)->nullable()->after('geo_country')
                  ->comment('ISO country code (IT, US, etc.)');
            $table->string('geo_region', 100)->nullable()->after('geo_country_code')
                  ->comment('Region/State name');
            $table->string('geo_city', 100)->nullable()->after('geo_region')
                  ->comment('City name');
            $table->string('geo_isp', 150)->nullable()->after('geo_city')
                  ->comment('Internet Service Provider name');
            $table->string('geo_timezone', 50)->nullable()->after('geo_isp')
                  ->comment('Timezone from IP (e.g., Europe/Rome)');
            
            // ==================== DEVICE HARDWARE ====================
            $table->string('device_type', 20)->nullable()->after('geo_timezone')
                  ->comment('Device type: Desktop, Mobile, Tablet');
            $table->string('device_os', 50)->nullable()->after('device_type')
                  ->comment('Operating system name and version');
            $table->string('device_browser', 50)->nullable()->after('device_os')
                  ->comment('Browser name and version');
            $table->string('screen_resolution', 20)->nullable()->after('device_browser')
                  ->comment('Screen resolution (e.g., 1920x1080)');
            $table->tinyInteger('cpu_cores')->unsigned()->nullable()->after('screen_resolution')
                  ->comment('Number of CPU cores');
            $table->smallInteger('ram_gb')->unsigned()->nullable()->after('cpu_cores')
                  ->comment('Approximate RAM in GB');
            $table->string('timezone_client', 50)->nullable()->after('ram_gb')
                  ->comment('Client-reported timezone');
            $table->string('language', 10)->nullable()->after('timezone_client')
                  ->comment('Browser language (e.g., it-IT)');
            $table->boolean('touch_support')->nullable()->after('language')
                  ->comment('Device has touch screen');

            // ==================== INDEXES ====================
            $table->index('device_fingerprint', 'idx_logs_device_fingerprint');
            $table->index('geo_country_code', 'idx_logs_geo_country');
            $table->index('geo_city', 'idx_logs_geo_city');
            $table->index('geo_isp', 'idx_logs_geo_isp');
            $table->index('device_type', 'idx_logs_device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_logs_device_fingerprint');
            $table->dropIndex('idx_logs_geo_country');
            $table->dropIndex('idx_logs_geo_city');
            $table->dropIndex('idx_logs_geo_isp');
            $table->dropIndex('idx_logs_device_type');

            // Drop columns
            $table->dropColumn([
                'device_fingerprint',
                'geo_country',
                'geo_country_code',
                'geo_region',
                'geo_city',
                'geo_isp',
                'geo_timezone',
                'device_type',
                'device_os',
                'device_browser',
                'screen_resolution',
                'cpu_cores',
                'ram_gb',
                'timezone_client',
                'language',
                'touch_support',
            ]);
        });
    }
};