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

    public function test_cannot_create_worklog_clashing_with_existing_one(): void
    {
        $project = $this->assignedProject();
        WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'date' => '2026-03-02',
                'start_time' => '11:00',
                'end_time' => '13:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'conflicting_work_log']);
        $this->assertSame(1, WorkLog::where('user_id', $this->user->id)->count());
    }

    public function test_worklog_may_start_when_the_previous_one_ends(): void
    {
        $project = $this->assignedProject();
        WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'date' => '2026-03-02',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ])
            ->assertStatus(201);
    }

    public function test_clash_check_is_scoped_to_the_same_user_and_date(): void
    {
        $project = $this->assignedProject();
        $other = User::factory()->create();
        $project->users()->attach($other->id, ['hourly_rate' => 50]);

        WorkLog::factory()->create([
            'user_id' => $other->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);
        WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-03',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'date' => '2026-03-02',
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertStatus(201);
    }

    public function test_running_worklog_blocks_a_later_entry_on_the_same_day(): void
    {
        $project = $this->assignedProject();
        WorkLog::factory()->active()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'date' => '2026-03-02',
                'start_time' => '14:00',
                'end_time' => '15:00',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'conflicting_work_log']);
    }

    public function test_cannot_update_worklog_into_a_clash(): void
    {
        $project = $this->assignedProject();
        WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);
        $workLog = WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '13:00',
            'end_time' => '14:00',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/worklogs/{$workLog->id}", ['start_time' => '11:30'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'conflicting_work_log']);
    }

    public function test_updating_a_worklog_does_not_clash_with_itself(): void
    {
        $project = $this->assignedProject();
        $workLog = WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/worklogs/{$workLog->id}", ['end_time' => '13:00'])
            ->assertStatus(200);
    }

    public function test_cannot_complete_tracking_into_a_clash(): void
    {
        $project = $this->assignedProject();
        $running = WorkLog::factory()->active()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
        ]);
        WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/worklogs/{$running->id}/complete", ['end_time' => '13:00'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'conflicting_work_log']);
    }

    public function test_admin_can_log_time_for_another_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $freelancer = User::factory()->create(['hourly_rate' => 40]);
        $project = Project::factory()->create(['hourly_rate' => 80]);
        $project->users()->attach($freelancer->id, ['hourly_rate' => 60]);

        $response = $this->actingAs($admin)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'user_id' => $freelancer->id,
                'date' => '2026-03-02',
                'start_time' => '09:00',
                'end_time' => '11:00',
            ]);

        $response->assertStatus(201);
        $this->assertEquals($freelancer->id, $response->json('user_id'));
        // The rate comes from the target user's pivot, not from the admin.
        $this->assertEquals(60, $response->json('user_hourly_rate'));
    }

    public function test_admin_cannot_log_time_for_a_user_outside_the_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $stranger = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($admin->id, ['hourly_rate' => 90]);

        $this->actingAs($admin)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'user_id' => $stranger->id,
                'date' => '2026-03-02',
                'start_time' => '09:00',
                'end_time' => '11:00',
            ])
            ->assertStatus(403);
    }

    public function test_freelancer_cannot_log_time_for_another_user(): void
    {
        $other = User::factory()->create();
        $project = $this->assignedProject();
        $project->users()->attach($other->id, ['hourly_rate' => 50]);

        $this->actingAs($this->user)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'user_id' => $other->id,
                'date' => '2026-03-02',
                'start_time' => '09:00',
                'end_time' => '11:00',
            ])
            ->assertStatus(403);

        $this->assertSame(0, WorkLog::count());
    }

    public function test_clash_check_follows_the_user_the_admin_books_for(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $freelancer = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($admin->id, ['hourly_rate' => 90]);
        $project->users()->attach($freelancer->id, ['hourly_rate' => 50]);

        // The admin's own day is full, the freelancer's is not.
        WorkLog::factory()->create([
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'user_id' => $freelancer->id,
                'date' => '2026-03-02',
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertStatus(201);

        // ... and the freelancer's own day now is.
        $this->actingAs($admin)
            ->postJson('/api/worklogs', [
                'project_id' => $project->id,
                'user_id' => $freelancer->id,
                'date' => '2026-03-02',
                'start_time' => '10:30',
                'end_time' => '12:00',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'conflicting_work_log']);
    }

    public function test_admin_can_reassign_a_worklog_to_another_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $freelancer = User::factory()->create();
        $project = Project::factory()->create(['hourly_rate' => 80]);
        $project->users()->attach($admin->id, ['hourly_rate' => 90]);
        $project->users()->attach($freelancer->id, ['hourly_rate' => 55]);

        $workLog = WorkLog::factory()->create([
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/worklogs/{$workLog->id}", ['user_id' => $freelancer->id]);

        $response->assertStatus(200);
        $this->assertEquals($freelancer->id, $response->json('user_id'));
        $this->assertEquals(55, $response->json('user_hourly_rate'));
    }

    public function test_freelancer_cannot_hand_a_worklog_to_someone_else(): void
    {
        $other = User::factory()->create();
        $project = $this->assignedProject();
        $workLog = WorkLog::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'date' => '2026-03-02',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/worklogs/{$workLog->id}", ['user_id' => $other->id])
            ->assertStatus(403);

        $this->assertEquals($this->user->id, $workLog->fresh()->user_id);
    }

    private function assignedProject(): Project
    {
        $project = Project::factory()->create(['hourly_rate' => 80]);
        $project->users()->attach($this->user->id, ['hourly_rate' => 60]);

        return $project;
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
