<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\GeoLocationService;
use Symfony\Component\HttpFoundation\Response;

class DeviceTrackingMiddleware
{
    protected GeoLocationService $geoService;

    public function __construct(GeoLocationService $geoService)
    {
        $this->geoService = $geoService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $deviceInfo = [
            // From Angular headers
            'device_fingerprint' => $request->header('X-Device-Fingerprint'),
            'device_type' => $request->header('X-Device-Type'),
            'device_os' => $request->header('X-Device-OS'),
            'device_browser' => $request->header('X-Device-Browser'),
            'screen_resolution' => $request->header('X-Screen-Resolution'),
            'cpu_cores' => $request->header('X-CPU-Cores') ? (int) $request->header('X-CPU-Cores') : null,
            'ram_gb' => $request->header('X-RAM-GB') ? (int) $request->header('X-RAM-GB') : null,
            'timezone_client' => $request->header('X-Timezone'),
            'language' => $request->header('X-Language'),
            'touch_support' => $request->header('X-Touch-Support') === 'true',
            
            // Standard
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            
            // Geo placeholders
            'geo_country' => null,
            'geo_country_code' => null,
            'geo_region' => null,
            'geo_city' => null,
            'geo_isp' => null,
            'geo_timezone' => null,
        ];

        // Enrich with geolocation
        $geoData = $this->geoService->lookup($request->ip());
        if ($geoData) {
            $deviceInfo['geo_country'] = $geoData['country'];
            $deviceInfo['geo_country_code'] = $geoData['country_code'];
            $deviceInfo['geo_region'] = $geoData['region'];
            $deviceInfo['geo_city'] = $geoData['city'];
            $deviceInfo['geo_isp'] = $geoData['isp'];
            $deviceInfo['geo_timezone'] = $geoData['timezone'];
        }

        // Make available globally
        $request->attributes->set('device_info', $deviceInfo);
        app()->instance('device_info', $deviceInfo);

        return $next($request);
    }
}