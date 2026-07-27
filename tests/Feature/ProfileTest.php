<?php

namespace Tests\Feature;

use App\Http\Middleware\Require2FASetup;
use App\Http\Middleware\Verify2FA;
use App\Models\User;
use App\Models\WorkLog;
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
            Require2FASetup::class,
            Verify2FA::class,
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

    public function test_profile_and_user_endpoints_expose_the_avatar(): void
    {
        $avatar = 'data:image/png;base64,'.str_repeat('A', 100);
        $this->user->avatar = $avatar;
        $this->user->save();

        $this->actingAs($this->user)->getJson('/api/profile')
            ->assertStatus(200)
            ->assertJson(['avatar' => $avatar]);

        $this->actingAs($this->user)->getJson('/api/user')
            ->assertStatus(200)
            ->assertJson(['avatar' => $avatar]);
    }

    public function test_oversized_avatar_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/profile', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'avatar' => str_repeat('A', 1048577),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('avatar');
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

    public function test_activity_returns_worklog_counts_per_day(): void
    {
        WorkLog::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'date' => '2026-07-10',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/profile/activity');

        $response->assertStatus(200);
        $activity = $response->json();
        $this->assertIsArray($activity);
        $day = collect($activity)->firstWhere('date', '2026-07-10');
        $this->assertIsArray($day);
        $this->assertEquals(2, $day['count']);
    }
}
