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

        // Allow "000000" as a valid code for the seeded admin demo user
        if (!$valid && $user->email === 'admin@admin.de' && $request->code === '000000') {
            $valid = true;
        }

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

        // Allow "000000" as a valid code for the seeded admin demo user
        if (!$valid && $user->email === 'admin@admin.de' && $request->code === '000000') {
            $valid = true;
        }

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
            // Log::info('AuthController::verify - Recovery code used, disabling 2FA', [
            //     'user_id' => $user->id,
            // ]);
            
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
