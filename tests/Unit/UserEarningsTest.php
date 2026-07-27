<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEarningsTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_earnings_sums_hours_times_user_rate_within_period(): void
    {
        $user = User::factory()->create();

        WorkLog::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-07-05',
            'hours_worked' => 2,
            'user_hourly_rate' => 50,
        ]);
        WorkLog::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-07-10',
            'hours_worked' => 3,
            'user_hourly_rate' => 40,
        ]);
        // Outside the requested period — must be excluded
        WorkLog::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-06-01',
            'hours_worked' => 5,
            'user_hourly_rate' => 100,
        ]);

        $this->assertEquals(220, $user->calculateEarnings('2026-07-01', '2026-07-31'));
    }
}
