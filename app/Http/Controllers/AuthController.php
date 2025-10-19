<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            Log::info('AuthController::login - User authenticated:', [
                'user_id' => $user->id,
                'has_2fa_enabled' => $user->hasTwoFactorEnabled(),
            ]);
            
            // Check if user has 2FA enabled
            if ($user->hasTwoFactorEnabled()) {
                // Get device fingerprint
                $fingerprint = $this->getDeviceFingerprint($request);
                
                Log::info('AuthController::login - Checking device trust:', [
                    'fingerprint' => $fingerprint,
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip(),
                ]);
                
                // Check if device is trusted
                $isDeviceTrusted = $user->isDeviceTrusted($fingerprint);
                
                Log::info('AuthController::login - Device trust check result:', [
                    'is_trusted' => $isDeviceTrusted,
                    'trusted_devices' => $user->two_factor_device_fingerprints,
                ]);
                
                if (!$isDeviceTrusted) {
                    // Logout temporarily but keep user ID in session for 2FA verification
                    session(['2fa_pending_user_id' => $user->id]);
                    Auth::logout();
                    
                    Log::info('AuthController::login - Requiring 2FA verification');
                    
                    return response()->json([
                        'message' => '2FA verification required',
                        'requires_2fa_verification' => true,
                    ]);
                }
                
                Log::info('AuthController::login - Device is trusted, allowing login');
            }
            
            // Check if user needs to setup 2FA
            if (!$user->hasTwoFactorEnabled()) {
                Log::info('AuthController::login - Requiring 2FA setup');
                return response()->json([
                    'message' => 'Logged in successfully',
                    'requires_2fa_setup' => true,
                ]);
            }
            
            Log::info('AuthController::login - Login successful');
            return response()->json(['message' => 'Logged in successfully']);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    
    /**
     * Get device fingerprint
     */
    protected function getDeviceFingerprint($request)
    {
        return hash('sha256', $request->ip() . $request->userAgent());
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
            Log::info('AuthController::user - Returning user data:', [
                'user_id' => $user->id,
                'has_two_factor_secret' => !is_null($user->two_factor_secret),
                'has_two_factor_confirmed_at' => !is_null($user->two_factor_confirmed_at),
                'has_two_factor_enabled_accessor' => $user->has_two_factor_enabled,
                'user_array' => $user->toArray()
            ]);
            return response()->json($user);
        }

        return response()->json(['message' => 'User not found'], 404);
    }
}