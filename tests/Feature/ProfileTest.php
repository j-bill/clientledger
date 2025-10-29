<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
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
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email', 'role'])
            ->assertJson(['email' => 'john@example.com']);
    }

    public function test_user_can_update_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/profile', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Jane Doe']);
    }

    public function test_user_can_view_profile_activity(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/profile/activity');

        $response->assertStatus(200)
            ->assertJsonStructure(['*' => ['type', 'description', 'created_at']]);
    }
}
