<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkLog;
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
            \App\Http\Middleware\Require2FASetup::class,
            \App\Http\Middleware\Verify2FA::class,
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
}
