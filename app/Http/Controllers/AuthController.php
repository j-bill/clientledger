<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\DeviceFingerprintService;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Check if user has 2FA enabled
            if ($user->hasTwoFactorEnabled()) {
                // Get device fingerprints using the same service as Verify2FA middleware
                $clientFingerprint = $request->header('X-Device-Fingerprint');
                $fingerprint = DeviceFingerprintService::generate($request, $clientFingerprint);
                
                // Check if device is trusted (check both server and client fingerprints)
                $isDeviceTrusted = $user->isDeviceTrusted($fingerprint, $clientFingerprint);
                
                if (!$isDeviceTrusted) {
                    // Logout temporarily but keep user ID in session for 2FA verification
                    session(['2fa_pending_user_id' => $user->id]);
                    Auth::logout();
                    
                    return response()->json([
                        'message' => '2FA verification required',
                        'requires_2fa_verification' => true,
                    ]);
                }
                
                // Device is trusted, set session flag
                session(['2fa_verified' => true]);
            }
            
            // Check if user needs to setup 2FA
            if (!$user->hasTwoFactorEnabled()) {
                return response()->json([
                    'message' => 'Logged in successfully',
                    'requires_2fa_setup' => true,
                ]);
            }
            
            return response()->json(['message' => 'Logged in successfully']);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = User::where('id', Auth::id())->first();
        
        if ($user) {
            // Log::info('AuthController::user - Returning user data:', [
            //     'user_id' => $user->id,
            //     'has_two_factor_secret' => !is_null($user->two_factor_secret),
            //     'has_two_factor_confirmed_at' => !is_null($user->two_factor_confirmed_at),
            //     'has_two_factor_enabled_accessor' => $user->has_two_factor_enabled,
            //     'user_array' => $user->toArray()
            // ]);
            return response()->json($user);
        }

        return response()->json(['message' => 'User not found'], 404);
    }
}