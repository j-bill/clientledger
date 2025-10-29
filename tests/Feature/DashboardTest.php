<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $freelancer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
        ]);

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->freelancer = User::factory()->create(['role' => 'freelancer']);
    }

    public function test_freelancer_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->freelancer)
            ->getJson('/api/dashboard');

        // Should succeed with 200 status
        $response->assertStatus(200);
        
        // Should have the expected data structure
        $response->assertJsonStructure([
            'kpis' => [
                'hours',
                'projects'
            ]
        ]);
    }

    public function test_admin_can_list_users(): void
    {
        // Simple test that doesn't trigger complex queries
        $response = $this->actingAs($this->user)
            ->getJson('/api/users');

        $response->assertStatus(200);
    }
}
