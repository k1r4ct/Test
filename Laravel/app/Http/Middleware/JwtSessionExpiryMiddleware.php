<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Services\SystemLogService;
use App\Models\User;

/**
 * Middleware to detect and log JWT session expiration
 * 
 * This middleware intercepts requests with expired JWT tokens and logs
 * the session expiration event before returning the 401 response.
 * It allows tracking user sessions that expire due to inactivity.
 */
class JwtSessionExpiryMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if no Authorization header present
        $token = $request->bearerToken();
        if (!$token) {
            return $next($request);
        }

        try {
            // Try to parse and validate the token
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            
            // Token is valid, continue with request
            return $next($request);
            
        } catch (TokenExpiredException $e) {
            // Token has expired - log this event
            $this->logSessionExpiry($token, $request, 'token_expired');
            
            // Let the request continue - the auth middleware will return 401
            return $next($request);
            
        } catch (TokenInvalidException $e) {
            // Token is invalid (tampered, malformed, etc.)
            $this->logSessionExpiry($token, $request, 'token_invalid');
            
            return $next($request);
            
        } catch (JWTException $e) {
            // Other JWT errors (could not decode, etc.)
            // Don't log these as they might be normal cases (no token, etc.)
            return $next($request);
        }
    }

    /**
     * Log the session expiration event
     *
     * @param string $token The expired/invalid JWT token
     * @param Request $request The current request
     * @param string $reason The reason for session end (token_expired, token_invalid)
     * @return void
     */
    private function logSessionExpiry(string $token, Request $request, string $reason): void
    {
        try {
            // Decode the token payload without validation to get user info
            // JWT tokens are base64 encoded, so we can still read the payload
            $tokenParts = explode('.', $token);
            
            if (count($tokenParts) !== 3) {
                return; // Not a valid JWT structure
            }

            $payloadBase64 = $tokenParts[1];
            $payloadJson = base64_decode(strtr($payloadBase64, '-_', '+/'));
            $payload = json_decode($payloadJson, true);

            if (!$payload || !isset($payload['sub'])) {
                return; // Could not decode payload or no subject (user_id)
            }

            $userId = $payload['sub'];
            $issuedAt = isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : null;
            $expiredAt = isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : null;

            // Try to get user details
            $user = User::find($userId);
            $userName = 'Unknown';
            $userEmail = 'Unknown';

            if ($user) {
                $userName = trim(($user->name ?? '') . ' ' . ($user->cognome ?? ''));
                $userEmail = $user->email ?? 'Unknown';
            }

            // Get device info if available
            $deviceInfo = $request->attributes->get('device_info', []);

            // Log the session expiration
            SystemLogService::auth()->warning('Session expired (automatic timeout)', [
                'user_id' => $userId,
                'email' => $userEmail,
                'name' => $userName,
                'reason' => $reason,
                'token_issued_at' => $issuedAt,
                'token_expired_at' => $expiredAt,
                'endpoint_accessed' => $request->path(),
                'http_method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => $deviceInfo['device_fingerprint'] ?? null,
                'device_type' => $deviceInfo['device_type'] ?? null,
                'device_browser' => $deviceInfo['device_browser'] ?? null,
                'geo_country' => $deviceInfo['geo_country'] ?? null,
                'geo_city' => $deviceInfo['geo_city'] ?? null,
            ]);

        } catch (\Exception $e) {
            // If logging fails, don't break the request
            // Optionally log to Laravel's default log
            \Log::warning('Failed to log session expiry: ' . $e->getMessage());
        }
    }
}
