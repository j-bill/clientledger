<?php

namespace Tests\Feature;

use App\Http\Middleware\Require2FASetup;
use App\Http\Middleware\Verify2FA;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Require2FASetup::class,
            Verify2FA::class,
        ]);

        $this->user = User::factory()->create();
    }

    public function test_user_can_view_worklogs(): void
    {
        WorkLog::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/worklogs');

        $response->assertStatus(200);
    }

    public function test_worklog_index_includes_calculated_amount(): void
    {
        WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'hours_worked' => 2,
            'user_hourly_rate' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/worklogs');

        $response->assertStatus(200);
        $this->assertEquals(100, $response->json('data.0.amount'));
    }

    public function test_user_can_log_time_on_assigned_project(): void
    {
        $project = Project::factory()->create(['hourly_rate' => 80]);
        $project->users()->attach($this->user->id, ['hourly_rate' => 60]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'billable' => true,
            ]);

        $response->assertStatus(201);
        $this->assertEquals(2, $response->json('hours_worked'));
        $this->assertEquals(60, $response->json('user_hourly_rate'));
    }

    public function test_user_cannot_log_time_on_unassigned_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_start_second_active_worklog(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id, ['hourly_rate' => 50]);
        WorkLog::factory()->active()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'start_time' => '09:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'active_work_log']);
    }

    public function test_worklog_index_clamps_per_page(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id, ['hourly_rate' => 50]);
        WorkLog::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);

        // 0 would otherwise mean "return every row".
        $this->actingAs($this->user)->getJson('/api/worklogs?per_page=0')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->user)->getJson('/api/worklogs?per_page=100000')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_worklog_index_does_not_embed_user_avatar(): void
    {
        $this->user->avatar = 'data:image/png;base64,'.str_repeat('A', 5000);
        $this->user->two_factor_device_fingerprints = ['abc123' => now()->toIso8601String()];
        $this->user->save();

        $project = Project::factory()->create();
        $project->users()->attach($this->user->id, ['hourly_rate' => 50]);
        WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/worklogs');

        $response->assertStatus(200)
            ->assertJsonMissingPath('data.0.user.avatar')
            ->assertJsonMissingPath('data.0.user.two_factor_device_fingerprints');
    }
}
