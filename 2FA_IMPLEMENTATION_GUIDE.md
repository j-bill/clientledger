# Complete Two-Factor Authentication (2FA) Implementation Guide

## Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Dependencies & Requirements](#dependencies--requirements)
4. [Database Schema](#database-schema)
5. [Backend Implementation](#backend-implementation)
6. [Frontend Implementation](#frontend-implementation)
7. [Configuration](#configuration)
8. [Security Considerations](#security-considerations)
9. [Testing & Validation](#testing--validation)
10. [Step-by-Step Implementation Guide](#step-by-step-implementation-guide)

---

## Overview

This 2FA implementation provides a complete, production-ready two-factor authentication system for Laravel/Vue.js applications. The system uses TOTP (Time-based One-Time Password) authentication via Google Authenticator or similar authenticator apps.

### Key Features
- TOTP-based authentication using Google Authenticator
- QR code generation for easy setup
- Recovery codes for account access if device is lost
- Trusted device management (2-week trust period)
- Middleware-based protection
- Seamless integration with Laravel Sanctum authentication
- Vue.js 3 frontend with Vuetify components

---

## System Architecture

### Authentication Flow

```
┌─────────────┐
│    Login    │
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│ Credentials Valid?  │──No──▶ [Return Error]
└──────┬──────────────┘
       │Yes
       ▼
┌─────────────────────┐
│  2FA Enabled?       │──No──▶ [Check if 2FA Required] ──▶ [2FA Setup Page]
└──────┬──────────────┘
       │Yes
       ▼
┌─────────────────────┐
│ Device Trusted?     │──Yes──▶ [Login Success]
└──────┬──────────────┘
       │No
       ▼
┌─────────────────────┐
│ 2FA Challenge Page  │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  Valid Code?        │──No──▶ [Try Recovery Code] ──▶ [Disable 2FA & Force Re-setup]
└──────┬──────────────┘
       │Yes
       ▼
┌─────────────────────┐
│  Trust Device?      │
└──────┬──────────────┘
       │
       ▼
[Login Success]
```

### Component Architecture

**Backend (Laravel)**
- Controllers: Handle 2FA operations
- Middleware: Enforce 2FA verification and setup
- Models: User model with 2FA methods
- Migrations: Database schema changes

**Frontend (Vue.js 3 + Vuetify)**
- Pages: TwoFactorSetup.vue, TwoFactorChallenge.vue, Profile.vue
- Router: Navigation guards for 2FA flows
- Store (Pinia): Authentication state management
- Axios Interceptors: Global 2FA redirect handling

---

## Dependencies & Requirements

### Backend Dependencies (composer.json)

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.0",
    "pragmarx/google2fa-laravel": "^2.3",
    "bacon/bacon-qr-code": "^3.0"
  }
}
```

**Installation:**
```bash
composer require pragmarx/google2fa-laravel
composer require bacon/bacon-qr-code
```

### Frontend Dependencies (package.json)

```json
{
  "dependencies": {
    "vue": "^3.5.13",
    "vue-router": "^4.5.0",
    "pinia": "^3.0.1",
    "vuetify": "^3.7.15",
    "axios": "^1.8.2"
  }
}
```

---

## Database Schema

### Migration File
**Location:** `database/migrations/2025_10_19_160232_add_two_factor_columns_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->json('two_factor_device_fingerprints')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'two_factor_device_fingerprints'
            ]);
        });
    }
};
```

### Database Columns Explained

| Column | Type | Purpose |
|--------|------|---------|
| `two_factor_secret` | TEXT | Stores encrypted TOTP secret key |
| `two_factor_recovery_codes` | TEXT | Stores encrypted JSON array of recovery codes |
| `two_factor_confirmed_at` | TIMESTAMP | Timestamp when 2FA was confirmed/enabled |
| `two_factor_device_fingerprints` | JSON | Array of trusted device fingerprints with expiry |

**Run Migration:**
```bash
php artisan migrate
```

---

## Backend Implementation

### 1. User Model Updates

**Location:** `app/Models/User.php`

Add these properties and methods to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        // ... other fields
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_device_fingerprints' => 'array',
    ];

    protected $appends = ['has_two_factor_enabled'];

    // 2FA Methods
    public function hasTwoFactorEnabled()
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorEnabled()
    {
        return $this->hasTwoFactorEnabled();
    }

    public function isDeviceTrusted($fingerprint)
    {
        if (!$this->two_factor_device_fingerprints) {
            return false;
        }

        foreach ($this->two_factor_device_fingerprints as $device) {
            if ($device['fingerprint'] === $fingerprint && $device['expires_at'] > now()->timestamp) {
                return true;
            }
        }

        return false;
    }

    public function addTrustedDevice($fingerprint, $userAgent)
    {
        $devices = $this->two_factor_device_fingerprints ?: [];
        
        // Remove old device with same fingerprint if exists
        $devices = array_filter($devices, function($device) use ($fingerprint) {
            return $device['fingerprint'] !== $fingerprint;
        });

        // Add new device (trust for 2 weeks)
        $devices[] = [
            'fingerprint' => $fingerprint,
            'user_agent' => $userAgent,
            'added_at' => now()->timestamp,
            'expires_at' => now()->addWeeks(2)->timestamp,
        ];

        // Keep only last 10 devices
        $devices = array_slice($devices, -10);

        $this->two_factor_device_fingerprints = array_values($devices);
        $this->save();
    }

    public function removeTrustedDevice($fingerprint)
    {
        if (!$this->two_factor_device_fingerprints) {
            return;
        }

        $devices = array_filter($this->two_factor_device_fingerprints, function($device) use ($fingerprint) {
            return $device['fingerprint'] !== $fingerprint;
        });

        $this->two_factor_device_fingerprints = array_values($devices);
        $this->save();
    }

    public function clearAllTrustedDevices()
    {
        $this->two_factor_device_fingerprints = [];
        $this->save();
    }

    public function getHasTwoFactorEnabledAttribute()
    {
        return $this->hasTwoFactorEnabled();
    }
}
```

### 2. TwoFactorAuthenticationController

**Location:** `app/Http/Controllers/TwoFactorAuthenticationController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TwoFactorAuthenticationController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new 2FA secret for the user
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        // Generate secret key
        $secret = $this->google2fa->generateSecretKey();
        
        // Store secret temporarily (not confirmed yet)
        $user->two_factor_secret = encrypt($secret);
        $user->save();

        // Generate QR code
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'secret' => $secret,
            'qr_code' => $qrCodeSvg,
        ]);
    }

    /**
     * Confirm 2FA setup by verifying a code
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json(['message' => '2FA not enabled'], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return response()->json(['message' => 'Invalid code'], 400);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        
        // Confirm 2FA
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        // Automatically trust the current device after initial setup
        $fingerprint = $this->getDeviceFingerprint($request);
        $user->addTrustedDevice($fingerprint, $request->userAgent());

        // Set session flag that 2FA is verified for this session
        session(['2fa_verified' => true]);

        return response()->json([
            'message' => '2FA enabled successfully',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Verify 2FA code during login
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'trust_device' => 'sometimes|boolean',
        ]);

        // Get user from pending session
        $userId = session('2fa_pending_user_id');
        if (!$userId) {
            return response()->json(['message' => 'No pending 2FA verification'], 400);
        }

        $user = \App\Models\User::find($userId);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);
        $usedRecoveryCode = false;

        if (!$valid) {
            // Try recovery codes
            $valid = $this->validateRecoveryCode($user, $request->code);
            $usedRecoveryCode = $valid;
        }

        if (!$valid) {
            return response()->json(['message' => 'Invalid code'], 400);
        }

        // If recovery code was used, disable 2FA and force re-setup
        if ($usedRecoveryCode) {
            Log::info('AuthController::verify - Recovery code used, disabling 2FA', [
                'user_id' => $user->id,
            ]);
            
            // Disable 2FA
            $user->two_factor_secret = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_confirmed_at = null;
            $user->two_factor_device_fingerprints = null;
            $user->save();

            // Log the user in
            Auth::login($user);
            $request->session()->regenerate();
            
            // Clear pending 2FA session
            session()->forget('2fa_pending_user_id');

            return response()->json([
                'message' => '2FA verified with recovery code. Please set up 2FA again.',
                'requires_2fa_setup' => true,
            ]);
        }

        // Trust this device if requested (default to true)
        if ($request->input('trust_device', true)) {
            $fingerprint = $this->getDeviceFingerprint($request);
            $user->addTrustedDevice($fingerprint, $request->userAgent());
        }

        // Log the user in
        Auth::login($user);
        $request->session()->regenerate();
        
        // Clear pending 2FA session
        session()->forget('2fa_pending_user_id');
        
        // Set session flag that 2FA is verified
        session(['2fa_verified' => true]);

        return response()->json([
            'message' => '2FA verified successfully',
        ]);
    }

    /**
     * Disable 2FA for the user
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password'], 400);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_device_fingerprints = null;
        $user->save();

        return response()->json(['message' => '2FA disabled successfully']);
    }

    /**
     * Get 2FA status
     */
    public function status(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'enabled' => $user->hasTwoFactorEnabled(),
            'confirmed' => !is_null($user->two_factor_confirmed_at),
            'trusted_devices_count' => count($user->two_factor_device_fingerprints ?: []),
        ]);
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->hasTwoFactorEnabled()) {
            return response()->json(['message' => '2FA not enabled'], 400);
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Get recovery codes
     */
    public function getRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->hasTwoFactorEnabled() || !$user->two_factor_recovery_codes) {
            return response()->json(['message' => '2FA not enabled or no recovery codes'], 400);
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Remove a trusted device
     */
    public function removeTrustedDevice(Request $request)
    {
        $request->validate([
            'fingerprint' => 'required|string',
        ]);

        $user = $request->user();
        $user->removeTrustedDevice($request->fingerprint);

        return response()->json(['message' => 'Device removed successfully']);
    }

    /**
     * Get list of trusted devices
     */
    public function getTrustedDevices(Request $request)
    {
        $user = $request->user();
        $devices = $user->two_factor_device_fingerprints ?: [];

        // Add current device indicator
        $currentFingerprint = $this->getDeviceFingerprint($request);
        
        $devices = array_map(function($device) use ($currentFingerprint) {
            $device['is_current'] = $device['fingerprint'] === $currentFingerprint;
            $device['added_at_human'] = \Carbon\Carbon::createFromTimestamp($device['added_at'])->diffForHumans();
            $device['expires_at_human'] = \Carbon\Carbon::createFromTimestamp($device['expires_at'])->diffForHumans();
            return $device;
        }, $devices);

        return response()->json(['devices' => array_values($devices)]);
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes()
    {
        return Collection::times(8, function () {
            return Str::random(10) . '-' . Str::random(10);
        })->all();
    }

    /**
     * Validate a recovery code
     */
    protected function validateRecoveryCode($user, $code)
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
            $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
            $user->save();

            return true;
        }

        return false;
    }

    /**
     * Get device fingerprint
     */
    protected function getDeviceFingerprint(Request $request)
    {
        return hash('sha256', $request->ip() . $request->userAgent());
    }
}
```


### 3. Middleware Implementation

#### Verify2FA Middleware
**Location:** `app/Http/Middleware/Verify2FA.php`

This middleware ensures that users with 2FA enabled verify their identity on new devices.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Verify2FA
{
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

    protected function getDeviceFingerprint(Request $request)
    {
        return hash('sha256', $request->ip() . $request->userAgent());
    }
}
```

#### Require2FASetup Middleware
**Location:** `app/Http/Middleware/Require2FASetup.php`

This middleware forces users to set up 2FA if they don't have it enabled.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FASetup
{
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
```

### 4. AuthController Integration

**Location:** `app/Http/Controllers/AuthController.php`

Update your login method to handle 2FA:

```php
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
                
                // Check if device is trusted
                $isDeviceTrusted = $user->isDeviceTrusted($fingerprint);
                
                if (!$isDeviceTrusted) {
                    // Logout temporarily but keep user ID in session for 2FA verification
                    session(['2fa_pending_user_id' => $user->id]);
                    Auth::logout();
                    
                    return response()->json([
                        'message' => '2FA verification required',
                        'requires_2fa_verification' => true,
                    ]);
                }
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
            return response()->json($user);
        }

        return response()->json(['message' => 'User not found'], 404);
    }
}
```

### 5. API Routes Configuration

**Location:** `routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Middleware\Require2FASetup;
use App\Http\Middleware\Verify2FA;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// 2FA verification route (public, but requires pending login)
Route::post('/2fa/verify', [TwoFactorAuthenticationController::class, 'verify']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // 2FA routes (accessible for initial setup and management)
    Route::prefix('2fa')->group(function () {
        Route::post('/enable', [TwoFactorAuthenticationController::class, 'enable']);
        Route::post('/confirm', [TwoFactorAuthenticationController::class, 'confirm']);
        Route::post('/disable', [TwoFactorAuthenticationController::class, 'disable']);
        Route::get('/status', [TwoFactorAuthenticationController::class, 'status']);
        Route::get('/recovery-codes', [TwoFactorAuthenticationController::class, 'getRecoveryCodes']);
        Route::post('/recovery-codes/regenerate', [TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes']);
        Route::get('/devices', [TwoFactorAuthenticationController::class, 'getTrustedDevices']);
        Route::delete('/devices', [TwoFactorAuthenticationController::class, 'removeTrustedDevice']);
    });
    
    // Routes that require 2FA setup and verification
    Route::middleware([Require2FASetup::class, Verify2FA::class])->group(function () {
        // Your protected routes here
        Route::get('/dashboard', function() {
            return response()->json(['message' => 'Dashboard']);
        });
        
        // Add all your other protected routes inside this group
    });
});
```

---

## Frontend Implementation

### 1. Axios Interceptor Setup

**Location:** `resources/js/bootstrap.js`

This interceptor automatically redirects users when 2FA is required:

```javascript
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add axios interceptor to handle 2FA requirements globally
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 403) {
      const data = error.response.data;
      
      // Handle 2FA setup required
      if (data.requires_2fa_setup) {
        import('./router').then(({ default: router }) => {
          router.push({ name: 'TwoFactorSetup' });
        });
        const suppressedError = new Error('2FA setup required');
        suppressedError.suppressSnackbar = true;
        return Promise.reject(suppressedError);
      }
      
      // Handle 2FA verification required
      if (data.requires_2fa_verification) {
        import('./router').then(({ default: router }) => {
          router.push({ name: 'TwoFactorChallenge' });
        });
        const suppressedError = new Error('2FA verification required');
        suppressedError.suppressSnackbar = true;
        return Promise.reject(suppressedError);
      }
    }
    
    return Promise.reject(error);
  }
);
```

### 2. Router Configuration

**Location:** `resources/js/router.js`

```javascript
import { createRouter, createWebHistory } from "vue-router";
import { store as createStore } from "./store";

import Login from "./pages/Login.vue";
import TwoFactorSetup from "./pages/TwoFactorSetup.vue";
import TwoFactorChallenge from "./pages/TwoFactorChallenge.vue";
// ... import other pages

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { requiresAuth: false },
  },
  {
    path: "/2fa/setup",
    name: "TwoFactorSetup",
    component: TwoFactorSetup,
    meta: { requiresAuth: true, skip2FACheck: true },
  },
  {
    path: "/2fa/challenge",
    name: "TwoFactorChallenge",
    component: TwoFactorChallenge,
    meta: { requiresAuth: false },
  },
  {
    path: "/",
    component: Home,
    meta: { requiresAuth: true },
  },
  // ... other routes
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Add navigation guard
router.beforeEach((to, from, next) => {
  const store = createStore();
  
  // Allow access to 2FA setup if pending flag is set (recovery code flow)
  if (to.name === "TwoFactorSetup" && sessionStorage.getItem('2fa_setup_pending')) {
    next();
    return;
  }
  
  // Check if user is authenticated
  if (to.meta.requiresAuth !== false && !store.isAuthenticated) {
    next({ name: "Login" });
    return;
  }

  // Redirect to login if already authenticated and trying to access login page
  if (to.name === "Login" && store.isAuthenticated) {
    next({ path: "/" });
    return;
  }

  next();
});

export default router;
```

### 3. Pinia Store Updates

**Location:** `resources/js/store.js`

Add 2FA-related state and actions:

```javascript
import { defineStore } from "pinia";
import axios from "axios";

export const store = defineStore("store", {
  state: () => ({
    user: null,
    // ... other state
  }),
  getters: {
    isAuthenticated(state) { 
      return !!state.user; 
    },
    has2FAEnabled(state) { 
      return state.user?.has_two_factor_enabled === true;
    },
  },
  actions: {
    async login(user, password) {
      return new Promise(async (resolve, reject) => {
        try {
          const response = await axios.post("/api/login", {
            email: user,
            password: password,
          });

          if (response.status === 200) {
            // Check if 2FA verification is required
            if (response.data.requires_2fa_verification) {
              sessionStorage.setItem('2fa_pending_email', user);
              resolve({ requires_2fa_verification: true, email: user });
              return;
            }

            // Check if 2FA setup is required
            if (response.data.requires_2fa_setup) {
              await this.getAuthUser();
              resolve({ requires_2fa_setup: true });
              return;
            }

            await this.getAuthUser();
            resolve();
          } else {
            reject("Login was unsuccessful.");
          }
        } catch (error) {
          this.showSnackbar(error.response?.data?.message || "Login failed", "error");
          reject(error.response?.data?.message || "Login failed");
        }
      });
    },
    
    async getAuthUser() {
      return new Promise(async (resolve, reject) => {
        try {
          const response = await axios.get("/api/user");
          this.user = response.data;
          resolve(this.user);
        } catch (error) {
          reject(error.response?.data?.message || "Failed to get user info");
        }
      });
    },
    // ... other actions
  }
});
```


### 4. Vue Components

#### Login.vue Component
**Location:** `resources/js/pages/Login.vue`

```vue
<template>
  <div class="login-container">
    <form @submit.prevent="handleLogin" class="login-form">
      <h2 class="pb-4">Login</h2>
      <div class="form-group">
        <v-text-field 
          v-model="form.email"
          type="email"
          variant="outlined"
          label="Email"
          required 
        />
      </div>
      <div class="form-group">
        <v-text-field 
          v-model="form.password"
          type="password"
          variant="outlined"
          label="Password"
          required 
        />
      </div>
      <v-btn 
        type="submit"
        block
        :loading="loading"
        color="primary"
      >
        Login
      </v-btn>
    </form>
  </div>
</template>

<script>
import { mapActions } from 'pinia'
import { store } from '../store'

export default {
  name: 'Login',
  data() {
    return {
      form: {
        email: '',
        password: ''
      },
      loading: false,
    }
  },
  methods: {
    ...mapActions(store, ['login']),
    async handleLogin() {
      this.loading = true
      try {
        const result = await this.login(this.form.email, this.form.password)
        
        // Handle 2FA verification required
        if (result?.requires_2fa_verification) {
          this.$router.push({
            name: 'TwoFactorChallenge',
            query: { email: result.email }
          })
          return
        }
        
        // Handle 2FA setup required
        if (result?.requires_2fa_setup) {
          this.$router.push({ name: 'TwoFactorSetup' })
          return
        }
        
        // Normal login success
        this.$router.push('/')
      } catch (error) {
        console.error('Login failed:', error)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

.login-form {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
}

.form-group {
  margin-bottom: 1rem;
}
</style>
```

#### TwoFactorSetup.vue Component
**Location:** `resources/js/pages/TwoFactorSetup.vue`

```vue
<template>
  <div class="two-factor-setup-container">
    <v-card class="setup-card" max-width="600" elevation="8">
      <v-card-title class="text-h5 pa-6">
        <v-icon left color="primary" size="large">mdi-shield-lock</v-icon>
        Set Up Two-Factor Authentication
      </v-card-title>

      <v-card-text class="pa-6">
        <v-stepper v-model="step" elevation="0">
          <v-stepper-header>
            <v-stepper-item 
              :complete="step > 1"
              :value="1"
              title="Generate QR Code"
            ></v-stepper-item>
            <v-divider></v-divider>
            <v-stepper-item 
              :complete="step > 2"
              :value="2"
              title="Verify Code"
            ></v-stepper-item>
            <v-divider></v-divider>
            <v-stepper-item 
              :value="3"
              title="Save Recovery Codes"
            ></v-stepper-item>
          </v-stepper-header>

          <v-stepper-window>
            <!-- Step 1: Generate QR Code -->
            <v-stepper-window-item :value="1">
              <div class="text-center py-6">
                <p class="mb-4">
                  Two-Factor Authentication adds an extra layer of security to your account.
                  You'll need an authenticator app like Google Authenticator or Authy.
                </p>
                <v-btn 
                  color="primary"
                  size="large"
                  :loading="loading"
                  @click="generateQRCode"
                >
                  Generate QR Code
                </v-btn>
              </div>
            </v-stepper-window-item>

            <!-- Step 2: Scan QR Code and Verify -->
            <v-stepper-window-item :value="2">
              <div class="text-center">
                <p class="mb-4">
                  Scan this QR code with your authenticator app:
                </p>
                <div 
                  v-if="qrCode"
                  class="qr-code-container mb-4"
                  v-html="qrCode"
                ></div>
                
                <div class="manual-entry-container mb-4">
                  <p class="text-caption text-medium-emphasis text-center mb-2">
                    <strong>Manual Entry:</strong><br>
                    If you can't scan the QR code, enter this key manually:
                  </p>
                  <code class="manual-key">{{ secret }}</code>
                </div>

                <v-text-field 
                  v-model="verificationCode"
                  label="Enter 6-digit code"
                  variant="outlined"
                  :rules="[rules.required, rules.sixDigits]"
                  maxlength="6"
                  @keyup.enter="verifyCode"
                  class="mb-4"
                ></v-text-field>

                <div class="d-flex">
                  <v-btn variant="outlined" @click="step = 1">
                    Back
                  </v-btn>
                  <v-spacer></v-spacer>
                  <v-btn 
                    color="primary"
                    :loading="loading"
                    @click="verifyCode"
                  >
                    Verify & Continue
                  </v-btn>
                </div>
              </div>
            </v-stepper-window-item>

            <!-- Step 3: Save Recovery Codes -->
            <v-stepper-window-item :value="3">
              <div>
                <v-alert type="warning" variant="tonal" class="mb-4">
                  <strong>Important:</strong> Save these recovery codes in a safe place. 
                  You can use them to access your account if you lose your phone.
                </v-alert>

                <v-card variant="outlined" class="recovery-codes-card mb-4">
                  <v-card-text>
                    <div class="recovery-codes">
                      <div 
                        v-for="(code, index) in recoveryCodes"
                        :key="index"
                        class="recovery-code"
                      >
                        {{ code }}
                      </div>
                    </div>
                  </v-card-text>
                </v-card>

                <div class="d-flex justify-space-between align-center mb-4">
                  <v-btn 
                    variant="outlined"
                    prepend-icon="mdi-content-copy"
                    @click="copyRecoveryCodes"
                  >
                    Copy All
                  </v-btn>
                  <v-btn 
                    variant="outlined"
                    prepend-icon="mdi-download"
                    @click="downloadRecoveryCodes"
                  >
                    Download
                  </v-btn>
                </div>

                <v-checkbox 
                  v-model="confirmedSaved"
                  label="I have saved my recovery codes in a safe place"
                  :rules="[rules.mustConfirm]"
                ></v-checkbox>

                <v-btn 
                  color="success"
                  block
                  size="large"
                  :disabled="!confirmedSaved"
                  @click="completeSetup"
                >
                  Complete Setup
                </v-btn>
              </div>
            </v-stepper-window-item>
          </v-stepper-window>
        </v-stepper>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import axios from 'axios'

export default {
  name: 'TwoFactorSetup',
  data() {
    return {
      step: 1,
      qrCode: null,
      secret: null,
      verificationCode: '',
      recoveryCodes: [],
      confirmedSaved: false,
      loading: false,
      rules: {
        required: v => !!v || 'This field is required',
        sixDigits: v => /^\d{6}$/.test(v) || 'Must be 6 digits',
        mustConfirm: v => v === true || 'You must confirm you have saved the recovery codes'
      }
    }
  },
  computed: {
    ...mapState(store, ['user']),
  },
  async mounted() {
    // Clear the pending flag if it exists (recovery code flow)
    if (sessionStorage.getItem('2fa_setup_pending')) {
      sessionStorage.removeItem('2fa_setup_pending')
    }
  },
  methods: {
    ...mapActions(store, ['showSnackbar', 'getAuthUser']),
    
    async generateQRCode() {
      this.loading = true
      try {
        const response = await axios.post('/api/2fa/enable')
        this.qrCode = response.data.qr_code
        this.secret = response.data.secret
        this.step = 2
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Failed to generate QR code', 'error')
      } finally {
        this.loading = false
      }
    },

    async verifyCode() {
      if (!this.verificationCode || this.verificationCode.length !== 6) {
        this.showSnackbar('Please enter a valid 6-digit code', 'error')
        return
      }

      this.loading = true
      try {
        const response = await axios.post('/api/2fa/confirm', {
          code: this.verificationCode
        })
        this.recoveryCodes = response.data.recovery_codes
        this.step = 3
        this.showSnackbar('2FA verified successfully!', 'success')
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Invalid verification code', 'error')
      } finally {
        this.loading = false
      }
    },

    copyRecoveryCodes() {
      const codesText = this.recoveryCodes.join('\n')
      navigator.clipboard.writeText(codesText)
      this.showSnackbar('Recovery codes copied to clipboard', 'success')
    },

    downloadRecoveryCodes() {
      const codesText = this.recoveryCodes.join('\n')
      const blob = new Blob([codesText], { type: 'text/plain' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = '2fa-recovery-codes.txt'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
      this.showSnackbar('Recovery codes downloaded', 'success')
    },

    async completeSetup() {
      // Refresh user data to get updated 2FA status
      await this.getAuthUser()
      
      // Mark in session storage that 2FA was just completed
      sessionStorage.setItem('2fa_just_completed', 'true')
      
      this.showSnackbar('2FA setup completed successfully!', 'success')
      
      // Small delay to ensure session is established
      await new Promise(resolve => setTimeout(resolve, 500))
      
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>
.two-factor-setup-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 2rem;
}

.setup-card {
  width: 100%;
}

.qr-code-container {
  display: flex;
  justify-content: center;
  padding: 1.5rem;
  background: white;
  border-radius: 8px;
  margin: 0 auto;
  width: fit-content;
}

.manual-key {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-family: monospace;
  font-size: 1.1rem;
  letter-spacing: 0.1em;
  display: block;
  text-align: center;
}

.recovery-codes {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.recovery-code {
  font-family: monospace;
  font-size: 0.9rem;
  padding: 0.5rem;
  background: white;
  border-radius: 4px;
  text-align: center;
}
</style>
```

#### TwoFactorChallenge.vue Component
**Location:** `resources/js/pages/TwoFactorChallenge.vue`

```vue
<template>
  <div class="two-factor-challenge-container">
    <form @submit.prevent="verifyCode" class="challenge-form">
      <div class="text-center mb-4">
        <v-icon color="primary" size="60" class="mb-3">
          mdi-shield-lock-outline
        </v-icon>
        <h2 class="mb-2">Two-Factor Authentication</h2>
        <p class="text-subtitle-1">
          Enter the 6-digit code from your authenticator app
        </p>
      </div>

      <div class="form-group">
        <v-text-field 
          v-model="code"
          label="Authentication Code"
          variant="outlined"
          type="text"
          inputmode="numeric"
          maxlength="6"
          :rules="[rules.required, rules.sixDigits]"
          autofocus
          @keyup.enter="verifyCode"
        />
      </div>

      <div class="form-group">
        <v-checkbox 
          v-model="trustDevice"
          label="Trust this device for 2 weeks"
          density="compact"
          hide-details
        ></v-checkbox>
      </div>

      <v-btn 
        type="submit"
        color="primary"
        block
        size="large"
        :loading="loading"
      >
        Verify
      </v-btn>

      <v-divider class="my-4"></v-divider>

      <div class="text-center">
        <v-btn 
          variant="text"
          size="small"
          @click="showRecoveryInput = !showRecoveryInput"
        >
          {{ showRecoveryInput ? 'Use authenticator code' : 'Use recovery code instead' }}
        </v-btn>
      </div>

      <v-expand-transition>
        <div v-if="showRecoveryInput" class="mt-4">
          <div class="form-group">
            <v-text-field 
              v-model="recoveryCode"
              label="Recovery Code"
              variant="outlined"
              placeholder="XXXXXXXXXX-XXXXXXXXXX"
              hint="Enter one of your recovery codes"
              @keyup.enter="verifyRecoveryCode"
            />
          </div>
          <v-btn 
            color="secondary"
            block
            :loading="loading"
            @click="verifyRecoveryCode"
          >
            Use Recovery Code
          </v-btn>
        </div>
      </v-expand-transition>

      <div class="text-center mt-4">
        <v-btn 
          variant="text"
          size="small"
          color="error"
          @click="cancelLogin"
        >
          Cancel and logout
        </v-btn>
      </div>
    </form>
  </div>
</template>

<script>
import { mapActions } from 'pinia'
import { store } from '../store'
import axios from 'axios'

export default {
  name: 'TwoFactorChallenge',
  data() {
    return {
      code: '',
      recoveryCode: '',
      trustDevice: true,
      showRecoveryInput: false,
      loading: false,
      rules: {
        required: v => !!v || 'This field is required',
        sixDigits: v => /^\d{6}$/.test(v) || 'Must be 6 digits'
      }
    }
  },
  methods: {
    ...mapActions(store, ['showSnackbar', 'getAuthUser']),

    async verifyCode() {
      if (!this.code || this.code.length !== 6) {
        this.showSnackbar('Please enter a valid 6-digit code', 'error')
        return
      }

      this.loading = true
      try {
        const response = await axios.post('/api/2fa/verify', {
          code: this.code,
          trust_device: this.trustDevice
        })

        // Check if 2FA setup is required (recovery code was used)
        if (response.data.requires_2fa_setup) {
          this.showSnackbar('Recovery code used. Please set up 2FA again for security.', 'warning', 5000)
          sessionStorage.setItem('2fa_setup_pending', 'true')
          this.$router.push({ name: 'TwoFactorSetup' })
          return
        }

        // Get user data and redirect
        await this.getAuthUser()
        this.showSnackbar('Login successful!', 'success')
        this.$router.push('/')
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Invalid code', 'error')
      } finally {
        this.loading = false
      }
    },

    async verifyRecoveryCode() {
      if (!this.recoveryCode) {
        this.showSnackbar('Please enter a recovery code', 'error')
        return
      }

      this.loading = true
      try {
        const response = await axios.post('/api/2fa/verify', {
          code: this.recoveryCode,
          trust_device: this.trustDevice
        })

        if (response.data.requires_2fa_setup) {
          this.showSnackbar('Recovery code used. Please set up 2FA again for security.', 'warning', 5000)
          sessionStorage.setItem('2fa_setup_pending', 'true')
          this.$router.push({ name: 'TwoFactorSetup' })
          return
        }

        await this.getAuthUser()
        this.showSnackbar('Login successful!', 'success')
        this.$router.push('/')
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Invalid recovery code', 'error')
      } finally {
        this.loading = false
      }
    },

    async cancelLogin() {
      await axios.post('/api/logout').catch(() => {})
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.two-factor-challenge-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

.challenge-form {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
  border-radius: 8px;
}

.form-group {
  margin-bottom: 1rem;
}
</style>
```


---

## Configuration

### Laravel Configuration

#### 1. Session Configuration
Ensure sessions are properly configured in `config/session.php`:

```php
'driver' => env('SESSION_DRIVER', 'file'),
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => false,
'encrypt' => false,
'files' => storage_path('framework/sessions'),
'connection' => env('SESSION_CONNECTION'),
'table' => 'sessions',
'store' => env('SESSION_STORE'),
'lottery' => [2, 100],
'cookie' => env(
    'SESSION_COOKIE',
    Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
),
'path' => '/',
'domain' => env('SESSION_DOMAIN'),
'secure' => env('SESSION_SECURE_COOKIE'),
'http_only' => true,
'same_site' => 'lax',
```

#### 2. CORS Configuration
If your frontend is on a different domain, configure CORS in `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

#### 3. Sanctum Configuration
Configure Sanctum in `config/sanctum.php`:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),

'guard' => ['web'],

'expiration' => null,

'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
],
```

#### 4. Environment Variables
Add to your `.env` file:

```env
APP_NAME=YourAppName
APP_URL=https://yourdomain.com
SESSION_DOMAIN=yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

### Frontend Configuration

#### 1. Axios Configuration
In `resources/js/bootstrap.js`, ensure axios is configured:

```javascript
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.baseURL = import.meta.env.VITE_API_URL || '';
```

#### 2. Vite Configuration
Configure Vite in `vite.config.js`:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
});
```

---

## Security Considerations

### 1. Device Fingerprinting
The device fingerprint is created using:
- IP Address
- User Agent string

```php
protected function getDeviceFingerprint(Request $request)
{
    return hash('sha256', $request->ip() . $request->userAgent());
}
```

**Limitations:**
- Will change if user's IP changes (e.g., switching from WiFi to mobile)
- Can be spoofed
- Consider adding more entropy if needed (e.g., browser features)

### 2. Secret Encryption
All sensitive data is encrypted:
- `two_factor_secret`: Encrypted using Laravel's encryption
- `two_factor_recovery_codes`: Encrypted JSON array

```php
$user->two_factor_secret = encrypt($secret);
$user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
```

### 3. Recovery Code Security
When a recovery code is used:
1. The code is immediately removed from the list
2. 2FA is completely disabled
3. User is forced to set up 2FA again

This prevents recovery code reuse and ensures compromised recovery codes don't persist.

### 4. Trust Duration
Devices are trusted for 2 weeks (configurable):

```php
'expires_at' => now()->addWeeks(2)->timestamp,
```

Adjust in `addTrustedDevice()` method as needed.

### 5. Session Management
- Session flag `2fa_verified` prevents repeated 2FA challenges in same session
- Session flag `2fa_pending_user_id` temporarily stores user ID during 2FA challenge
- Sessions are regenerated after successful login

### 6. Rate Limiting
**Recommended:** Add rate limiting to 2FA endpoints:

```php
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/2fa/verify', [TwoFactorAuthenticationController::class, 'verify']);
});
```

### 7. HTTPS Requirement
**Critical:** Always use HTTPS in production. 2FA secrets and codes transmitted over HTTP can be intercepted.

---

## Testing & Validation

### Manual Testing Checklist

#### Initial Setup Flow
- [ ] New user logs in
- [ ] Redirected to 2FA setup page
- [ ] QR code generates successfully
- [ ] Can scan QR code with Google Authenticator
- [ ] Manual key entry works
- [ ] Invalid code shows error
- [ ] Valid code proceeds to recovery codes
- [ ] Recovery codes display correctly
- [ ] Can copy recovery codes
- [ ] Can download recovery codes
- [ ] Complete setup redirects to dashboard

#### Login with 2FA Flow
- [ ] User with 2FA logs in
- [ ] On trusted device: direct login
- [ ] On new device: redirected to 2FA challenge
- [ ] Valid code allows login
- [ ] Invalid code shows error
- [ ] "Trust device" checkbox works
- [ ] Trusted device persists for 2 weeks

#### Recovery Code Flow
- [ ] Can use recovery code instead of TOTP
- [ ] Recovery code is consumed after use
- [ ] 2FA is disabled after recovery code use
- [ ] User is forced to set up 2FA again

#### 2FA Management
- [ ] Can view 2FA status in profile
- [ ] Can view trusted devices
- [ ] Can remove trusted device
- [ ] Can regenerate recovery codes
- [ ] Can disable 2FA (with password confirmation)
- [ ] Can re-enable 2FA

### Automated Testing

#### Backend Tests (PHPUnit)

Create `tests/Feature/TwoFactorAuthenticationTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_2fa()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/2fa/enable');
        
        $response->assertStatus(200)
            ->assertJsonStructure(['secret', 'qr_code']);
        
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }
    
    public function test_user_can_confirm_2fa_with_valid_code()
    {
        $user = User::factory()->create();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        $user->two_factor_secret = encrypt($secret);
        $user->save();
        
        $validCode = $google2fa->getCurrentOtp($secret);
        
        $response = $this->actingAs($user)
            ->postJson('/api/2fa/confirm', ['code' => $validCode]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['recovery_codes']);
        
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }
    
    public function test_2fa_verification_required_on_new_device()
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['requires_2fa_verification' => true]);
    }
}
```

#### Frontend Tests (Playwright)

Create `tests/e2e/2fa.spec.js`:

```javascript
import { test, expect } from '@playwright/test';

test.describe('2FA Setup Flow', () => {
  test('should complete 2FA setup', async ({ page }) => {
    // Login
    await page.goto('/login');
    await page.fill('[data-test="login-email"]', 'test@example.com');
    await page.fill('[data-test="login-password"]', 'password');
    await page.click('[data-test="btn-login"]');
    
    // Should redirect to 2FA setup
    await expect(page).toHaveURL('/2fa/setup');
    
    // Generate QR code
    await page.click('text=Generate QR Code');
    await expect(page.locator('.qr-code-container')).toBeVisible();
    
    // Note: In real tests, you'd need to extract the secret and generate a valid TOTP
    // For demo, we'll assume a test code
    await page.fill('input[label="Enter 6-digit code"]', '123456');
    await page.click('text=Verify & Continue');
    
    // Should show recovery codes
    await expect(page.locator('.recovery-codes')).toBeVisible();
    
    // Complete setup
    await page.check('text=I have saved my recovery codes');
    await page.click('text=Complete Setup');
    
    // Should redirect to dashboard
    await expect(page).toHaveURL('/');
  });
});

test.describe('2FA Login Flow', () => {
  test('should require 2FA on new device', async ({ page }) => {
    // Login with 2FA enabled user
    await page.goto('/login');
    await page.fill('[data-test="login-email"]', '2fa-user@example.com');
    await page.fill('[data-test="login-password"]', 'password');
    await page.click('[data-test="btn-login"]');
    
    // Should redirect to 2FA challenge
    await expect(page).toHaveURL('/2fa/challenge');
    
    // Enter valid code
    await page.fill('input[label="Authentication Code"]', '123456');
    await page.click('text=Verify');
    
    // Should redirect to dashboard
    await expect(page).toHaveURL('/');
  });
});
```

---

## Step-by-Step Implementation Guide

### Phase 1: Backend Setup (1-2 hours)

1. **Install Dependencies**
   ```bash
   composer require pragmarx/google2fa-laravel
   composer require bacon/bacon-qr-code
   ```

2. **Create and Run Migration**
   ```bash
   php artisan make:migration add_two_factor_columns_to_users_table
   # Copy migration content from this guide
   php artisan migrate
   ```

3. **Update User Model**
   - Add casts, hidden fields, and appends
   - Copy all 2FA methods from this guide

4. **Create TwoFactorAuthenticationController**
   ```bash
   php artisan make:controller TwoFactorAuthenticationController
   # Copy controller content from this guide
   ```

5. **Create Middleware**
   ```bash
   php artisan make:middleware Verify2FA
   php artisan make:middleware Require2FASetup
   # Copy middleware content from this guide
   ```

6. **Update AuthController**
   - Add 2FA checks to login method
   - Add device fingerprinting

7. **Configure Routes**
   - Add 2FA routes in `routes/api.php`
   - Apply middleware to protected routes

### Phase 2: Frontend Setup (2-3 hours)

1. **Install Frontend Dependencies**
   ```bash
   npm install vue@^3 vue-router@^4 pinia@^3 vuetify@^3 axios
   ```

2. **Setup Axios Interceptor**
   - Update `resources/js/bootstrap.js`
   - Add 2FA redirect handling

3. **Create Vue Components**
   - Create `TwoFactorSetup.vue`
   - Create `TwoFactorChallenge.vue`
   - Update `Login.vue`

4. **Configure Router**
   - Add 2FA routes
   - Add navigation guards

5. **Update Pinia Store**
   - Add 2FA state management
   - Update login action

6. **Build and Test**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

### Phase 3: Testing & Refinement (1-2 hours)

1. **Manual Testing**
   - Test complete setup flow
   - Test login with 2FA
   - Test recovery code flow
   - Test device trust

2. **Security Review**
   - Verify HTTPS in production
   - Add rate limiting
   - Review session configuration

3. **User Experience**
   - Add loading states
   - Improve error messages
   - Add help text

### Phase 4: Production Deployment

1. **Environment Configuration**
   ```bash
   # Update .env
   APP_URL=https://yourdomain.com
   SESSION_DOMAIN=yourdomain.com
   SANCTUM_STATEFUL_DOMAINS=yourdomain.com
   ```

2. **Deploy**
   ```bash
   # Clear caches
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   
   # Build frontend
   npm run build
   
   # Run migrations
   php artisan migrate --force
   ```

3. **Post-Deployment Verification**
   - Test 2FA setup flow
   - Test login flow
   - Monitor logs for errors

---

## Troubleshooting

### Common Issues

#### Issue: "No pending 2FA verification" error
**Solution:** Ensure session is properly configured and shared between frontend and backend.

#### Issue: QR code not displaying
**Solution:** Check that `bacon/bacon-qr-code` is installed and QR code SVG is being rendered correctly.

#### Issue: Invalid code even with correct TOTP
**Solution:** 
- Verify server time is synchronized (TOTP is time-based)
- Check timezone configuration
- Allow for time drift in verification

#### Issue: Device not trusted after selecting "Trust device"
**Solution:** Verify device fingerprint is being calculated consistently and stored correctly.

#### Issue: Session lost between requests
**Solution:**
- Ensure cookies are being set with correct domain
- Verify CORS configuration
- Check that `withCredentials: true` is set in axios

### Debug Mode

Add logging to help troubleshoot:

```php
Log::info('2FA Debug', [
    'user_id' => $user->id,
    'has_secret' => !is_null($user->two_factor_secret),
    'has_confirmed' => !is_null($user->two_factor_confirmed_at),
    'device_fingerprint' => $fingerprint,
    'is_trusted' => $user->isDeviceTrusted($fingerprint),
]);
```

---

## Additional Features

### Optional Enhancements

1. **Email Notifications**
   - Notify user when 2FA is enabled/disabled
   - Alert when new device is trusted
   - Warn when recovery code is used

2. **Backup Methods**
   - SMS verification as fallback
   - Email verification as fallback

3. **Enhanced Device Management**
   - Show device location (using IP geolocation)
   - Show device type (desktop/mobile)
   - Allow device nicknames

4. **Admin Features**
   - Force 2FA for all users
   - Disable user's 2FA (with audit log)
   - View 2FA adoption rates

---

## Resources

### Libraries Used
- **PragmaRX Google2FA**: https://github.com/antonioribeiro/google2fa
- **Bacon QR Code**: https://github.com/Bacon/BaconQrCode
- **Laravel Sanctum**: https://laravel.com/docs/sanctum

### Compatible Authenticator Apps
- Google Authenticator (iOS/Android)
- Microsoft Authenticator (iOS/Android)
- Authy (iOS/Android/Desktop)
- 1Password (Cross-platform)
- LastPass Authenticator (iOS/Android)

### Further Reading
- [RFC 6238: TOTP](https://tools.ietf.org/html/rfc6238)
- [OWASP 2FA Guide](https://cheatsheetseries.owasp.org/cheatsheets/Multifactor_Authentication_Cheat_Sheet.html)
- [Laravel Authentication](https://laravel.com/docs/authentication)

---

## License

This implementation guide is provided as-is for reference and education. Adapt as needed for your specific use case.

**Created:** 2025-10-23
**Version:** 1.0

