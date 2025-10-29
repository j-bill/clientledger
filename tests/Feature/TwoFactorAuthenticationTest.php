<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
        ]);

        $this->user = User::factory()->create([
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function test_user_can_enable_2fa(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/2fa/enable');

        $response->assertStatus(200);
    }
}
