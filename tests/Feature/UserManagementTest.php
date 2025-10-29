<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $user->id, 'email' => 'test@example.com']);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$user->id}", [
                'name' => 'Updated Name',
                'role' => 'admin',
            ]);

        $response->assertStatus(200);
    }
}
