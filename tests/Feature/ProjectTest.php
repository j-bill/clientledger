<?php

namespace Tests\Feature;

use App\Http\Middleware\Require2FASetup;
use App\Http\Middleware\Verify2FA;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Require2FASetup::class,
            Verify2FA::class,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_projects(): void
    {
        Project::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/projects');

        $response->assertStatus(200);
    }

    public function test_admin_can_update_project(): void
    {
        $project = Project::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);
    }

    public function test_freelancer_only_sees_assigned_projects(): void
    {
        $freelancer = User::factory()->create(['role' => 'freelancer']);
        $assigned = Project::factory()->create();
        Project::factory()->create(); // unassigned
        $assigned->users()->attach($freelancer->id, ['hourly_rate' => 50]);

        $response = $this->actingAs($freelancer)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJson([['id' => $assigned->id]]);
    }
}
