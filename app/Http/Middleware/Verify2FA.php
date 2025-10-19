<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Verify2FA
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if no user is authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip for 2FA-related routes and logout
        if ($request->is('api/2fa/*') || $request->is('api/logout')) {
            return $next($request);
        }

        // Skip if user doesn't have 2FA enabled
        if (!$user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        // Get device fingerprint
        $fingerprint = $this->getDeviceFingerprint($request);

        // Check if device is trusted
        if ($user->isDeviceTrusted($fingerprint)) {
            return $next($request);
        }

        // Check if 2FA was verified in this session
        if (session('2fa_verified')) {
            return $next($request);
        }

        // Require 2FA verification
        return response()->json([
            'message' => '2FA verification required',
            'requires_2fa_verification' => true,
        ], 403);
    }

    /**
     * Get device fingerprint
     */
    protected function getDeviceFingerprint(Request $request)
    {
        return hash('sha256', $request->ip() . $request->userAgent());
    }
}
