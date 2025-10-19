<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FASetup
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

        // Skip for 2FA-related routes
        if ($request->is('api/2fa/*') || $request->is('api/logout')) {
            return $next($request);
        }

        // If user doesn't have 2FA enabled, return a specific response
        if (!$user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => '2FA setup required',
                'requires_2fa_setup' => true,
            ], 403);
        }

        return $next($request);
    }
}
