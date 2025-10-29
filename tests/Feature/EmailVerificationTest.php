<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $verifiedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
        ]);

        $this->user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    public function test_user_can_check_email_verification_status(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/email-verification/status');

        $response->assertStatus(200)
            ->assertJson(['email_verified' => false]);
    }

    public function test_verified_user_email_status_shows_verified(): void
    {
        $response = $this->actingAs($this->verifiedUser)
            ->getJson('/api/email-verification/status');

        $response->assertStatus(200)
            ->assertJson(['email_verified' => true]);
    }
}
