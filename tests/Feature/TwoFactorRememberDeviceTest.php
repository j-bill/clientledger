<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DeviceFingerprintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorRememberDeviceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        
        $google2fa = new Google2FA();
        $this->secret = $google2fa->generateSecretKey();
        
        $this->user = User::factory()->create([
            'two_factor_secret' => encrypt($this->secret),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_trusted_device_bypasses_2fa_verification(): void
    {
        // Simulate adding a trusted device directly
        $headers = [
            'User-Agent' => 'TestAgent/1.0',
            'Accept-Language' => 'en-US',
            'Accept-Encoding' => 'gzip',
            'X-Device-Fingerprint' => 'test-client-fingerprint',
        ];

        // Create a mock request to generate the fingerprint
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $headers['User-Agent'],
            'HTTP_ACCEPT_LANGUAGE' => $headers['Accept-Language'],
            'HTTP_ACCEPT_ENCODING' => $headers['Accept-Encoding'],
            'HTTP_X_DEVICE_FINGERPRINT' => $headers['X-Device-Fingerprint'],
        ]);
        
        $fingerprint = DeviceFingerprintService::generate($request, $headers['X-Device-Fingerprint']);
        
        // Add trusted device directly to user
        $this->user->addTrustedDevice($fingerprint, $headers['User-Agent'], $headers['X-Device-Fingerprint']);
        
        // Acting as user (simulates being logged in)
        // Hit protected route - should SUCCEED because device is trusted
        $response = $this->actingAs($this->user)
            ->getJson('/api/profile', $headers);

        $response->assertStatus(200);
    }

    public function test_untrusted_device_requires_2fa_verification(): void
    {
        // First add a trusted device for device A
        $headersA = [
            'User-Agent' => 'DeviceA',
            'X-Device-Fingerprint' => 'fingerprint-a',
        ];
        
        $requestA = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $headersA['User-Agent'],
            'HTTP_X_DEVICE_FINGERPRINT' => $headersA['X-Device-Fingerprint'],
        ]);
        
        $fingerprintA = DeviceFingerprintService::generate($requestA, $headersA['X-Device-Fingerprint']);
        $this->user->addTrustedDevice($fingerprintA, $headersA['User-Agent'], $headersA['X-Device-Fingerprint']);

        // Now try to access from device B (different fingerprint)
        $headersB = [
            'User-Agent' => 'DeviceB',
            'X-Device-Fingerprint' => 'fingerprint-b',
        ];

        $response = $this->actingAs($this->user)
            ->getJson('/api/profile', $headersB);

        // Expect 403 because device B is not trusted
        $response->assertStatus(403)
            ->assertJson([
                'message' => '2FA verification required',
                'requires_2fa_verification' => true,
            ]);
    }

    public function test_user_without_2fa_enabled_is_required_to_setup(): void
    {
        // Create user without 2FA
        $userWithout2FA = User::factory()->create([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($userWithout2FA)
            ->getJson('/api/profile');

        // User without 2FA should be prompted to set it up
        $response->assertStatus(403)
            ->assertJson([
                'message' => '2FA setup required',
                'requires_2fa_setup' => true,
            ]);
    }

    public function test_session_2fa_verified_flag_allows_access(): void
    {
        // User has 2FA but no trusted device - session flag should work
        $response = $this->actingAs($this->user)
            ->withSession(['2fa_verified' => true])
            ->getJson('/api/profile');

        $response->assertStatus(200);
    }

    public function test_client_fingerprint_fallback_works(): void
    {
        // Add device with only client fingerprint
        $clientFingerprint = 'unique-client-fingerprint-123';
        
        // Store a trusted device with a specific client fingerprint
        $this->user->two_factor_device_fingerprints = [
            [
                'fingerprint' => 'some-server-fingerprint',
                'client_fingerprint' => $clientFingerprint,
                'user_agent' => 'TestAgent',
                'added_at' => now()->timestamp,
                'expires_at' => now()->addDays(90)->timestamp,
            ]
        ];
        $this->user->save();
        
        // Access with matching client fingerprint but different server fingerprint
        $headers = [
            'User-Agent' => 'DifferentAgent',
            'X-Device-Fingerprint' => $clientFingerprint,
        ];

        $response = $this->actingAs($this->user)
            ->getJson('/api/profile', $headers);

        $response->assertStatus(200);
    }

    public function test_expired_device_requires_2fa(): void
    {
        // Add an expired trusted device
        $this->user->two_factor_device_fingerprints = [
            [
                'fingerprint' => 'expired-fingerprint',
                'client_fingerprint' => 'expired-client',
                'user_agent' => 'TestAgent',
                'added_at' => now()->subDays(100)->timestamp,
                'expires_at' => now()->subDays(10)->timestamp, // Expired
            ]
        ];
        $this->user->save();
        
        $headers = [
            'User-Agent' => 'TestAgent',
            'X-Device-Fingerprint' => 'expired-client',
        ];

        $response = $this->actingAs($this->user)
            ->getJson('/api/profile', $headers);

        // Should require 2FA because device is expired
        $response->assertStatus(403);
    }
}
