<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * GeoLocationService
 * 
 * IP geolocation using free ip-api.com service (45 requests/minute).
 * Results are cached for 24 hours.
 */
class GeoLocationService
{
    private const API_URL = 'http://ip-api.com/json/';
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Lookup geolocation data for an IP address
     */
    public function lookup(string $ip): ?array
    {
        // Skip private/local IPs
        if ($this->isPrivateIP($ip)) {
            return $this->getLocalIPData();
        }

        // Check cache first
        $cacheKey = 'geo_ip_' . md5($ip);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(5)->get(self::API_URL . $ip, [
                'fields' => 'status,country,countryCode,regionName,city,isp,timezone'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (($data['status'] ?? '') === 'success') {
                    $result = [
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'isp' => $data['isp'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                    ];

                    Cache::put($cacheKey, $result, self::CACHE_TTL);
                    return $result;
                }
            }
        } catch (\Exception $e) {
            Log::warning('GeoLocation lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if IP is private/local
     */
    private function isPrivateIP(string $ip): bool
    {
        if ($ip === '::1' || $ip === '127.0.0.1') {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Return placeholder data for local IPs
     */
    private function getLocalIPData(): array
    {
        return [
            'country' => 'Local Network',
            'country_code' => 'LO',
            'region' => 'Local',
            'city' => 'Localhost',
            'isp' => 'Local Network',
            'timezone' => config('app.timezone', 'Europe/Rome'),
        ];
    }
}