<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DeviceFingerprintService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new 2FA secret for the user
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        // Generate secret key
        $secret = $this->google2fa->generateSecretKey();

        // Store secret temporarily (not confirmed yet)
        $user->two_factor_secret = encrypt($secret);
        $user->save();

        // Generate QR code
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config()->string('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
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
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'client_fingerprint' => 'sometimes|string',
        ]);

        $user = $this->requireUser();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => '2FA not enabled'], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $code = $request->string('code')->toString();
        $valid = is_string($secret) && $this->google2fa->verifyKey($secret, $code);

        // Allow "000000" as a valid code for the seeded admin demo user
        if (! $valid && $user->email === 'admin@admin.de' && $code === '000000') {
            $valid = true;
        }

        if (! $valid) {
            return response()->json(['message' => 'Invalid code'], 400);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Confirm 2FA
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        // Automatically trust the current device after initial setup
        $requestFingerprint = $request->input('client_fingerprint');
        $clientFingerprint = is_string($requestFingerprint) ? $requestFingerprint : $request->header('X-Device-Fingerprint');
        $fingerprint = DeviceFingerprintService::generate($request, $clientFingerprint);
        $deviceInfo = DeviceFingerprintService::getDeviceInfo($request);
        $user->addTrustedDevice($fingerprint, $request->userAgent(), $clientFingerprint, $deviceInfo);

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
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'trust_device' => 'sometimes|boolean',
            'client_fingerprint' => 'sometimes|string',
        ]);

        // Get user from pending session
        $userId = session('2fa_pending_user_id');
        if (! is_int($userId)) {
            return response()->json(['message' => 'No pending 2FA verification'], 400);
        }

        $user = User::find($userId);

        if (! $user || $user->two_factor_secret === null || ! $user->twoFactorEnabled()) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $code = $request->string('code')->toString();
        $valid = is_string($secret) && $this->google2fa->verifyKey($secret, $code);
        $usedRecoveryCode = false;

        // Allow "000000" as a valid code for the seeded admin demo user
        if (! $valid && $user->email === 'admin@admin.de' && $code === '000000') {
            $valid = true;
        }

        if (! $valid) {
            // Try recovery codes
            $valid = $this->validateRecoveryCode($user, $code);
            $usedRecoveryCode = $valid;
        }

        if (! $valid) {
            return response()->json(['message' => 'Invalid code'], 400);
        }

        // If recovery code was used, disable 2FA and force re-setup
        if ($usedRecoveryCode) {
            // Disable 2FA
            $user->two_factor_secret = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_confirmed_at = null;
            $user->two_factor_device_fingerprints = null;
            $user->save();

            // Log the user in
            Auth::guard('web')->login($user);
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
            $requestFingerprint = $request->input('client_fingerprint');
            $clientFingerprint = is_string($requestFingerprint) ? $requestFingerprint : $request->header('X-Device-Fingerprint');
            $fingerprint = DeviceFingerprintService::generate($request, $clientFingerprint);
            $deviceInfo = DeviceFingerprintService::getDeviceInfo($request);
            $user->addTrustedDevice($fingerprint, $request->userAgent(), $clientFingerprint, $deviceInfo);
        }

        // Log the user in
        Auth::guard('web')->login($user);
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
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $this->requireUser();

        // Verify password
        if (! Hash::check($request->string('password')->toString(), $user->password)) {
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
    public function status(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        return response()->json([
            'enabled' => $user->twoFactorEnabled(),
            'confirmed' => ! is_null($user->two_factor_confirmed_at),
            'trusted_devices_count' => count($user->two_factor_device_fingerprints ?: []),
        ]);
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        if (! $user->twoFactorEnabled()) {
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
    public function getRecoveryCodes(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        if (! $user->twoFactorEnabled() || ! $user->two_factor_recovery_codes) {
            return response()->json(['message' => '2FA not enabled or no recovery codes'], 400);
        }

        $decrypted = decrypt($user->two_factor_recovery_codes);
        $recoveryCodes = is_string($decrypted) ? json_decode($decrypted, true) : [];

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Remove a trusted device
     */
    public function removeTrustedDevice(Request $request): JsonResponse
    {
        $request->validate([
            'fingerprint' => 'required|string',
        ]);

        $user = $this->requireUser();
        $user->removeTrustedDevice($request->string('fingerprint')->toString());

        return response()->json(['message' => 'Device removed successfully']);
    }

    /**
     * Get list of trusted devices
     */
    public function getTrustedDevices(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $devices = $user->two_factor_device_fingerprints ?: [];

        // Add current device indicator
        $clientFingerprint = $request->header('X-Device-Fingerprint');
        $currentFingerprint = DeviceFingerprintService::generate($request, $clientFingerprint);

        $devices = array_map(function ($device) use ($currentFingerprint) {
            if (! is_array($device)) {
                return null;
            }

            $addedAt = $device['added_at'] ?? null;
            $expiresAt = $device['expires_at'] ?? null;

            $device['is_current'] = ($device['fingerprint'] ?? null) === $currentFingerprint;
            $device['added_at_human'] = is_numeric($addedAt) ? Carbon::createFromTimestamp($addedAt)->diffForHumans() : null;
            $device['expires_at_human'] = is_numeric($expiresAt) ? Carbon::createFromTimestamp($expiresAt)->diffForHumans() : null;

            return $device;
        }, $devices);

        return response()->json(['devices' => array_values(array_filter($devices))]);
    }

    /**
     * Generate recovery codes
     */
    /**
     * @return array<int, string>
     */
    protected function generateRecoveryCodes(): array
    {
        return Collection::times(8, function () {
            return Str::random(10).'-'.Str::random(10);
        })->all();
    }

    /**
     * Validate a recovery code
     */
    protected function validateRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $decrypted = decrypt($user->two_factor_recovery_codes);
        $recoveryCodes = is_string($decrypted) ? json_decode($decrypted, true) : null;

        if (! is_array($recoveryCodes)) {
            return false;
        }

        // Stored codes are strings; drop anything else defensively.
        $recoveryCodes = array_values(array_filter($recoveryCodes, 'is_string'));

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
     * Get device fingerprint using the DeviceFingerprintService.
     *
     * @deprecated Use DeviceFingerprintService::generate() directly
     */
    protected function getDeviceFingerprint(Request $request): string
    {
        $clientFingerprint = $request->header('X-Device-Fingerprint');

        return DeviceFingerprintService::generate($request, $clientFingerprint);
    }
}
