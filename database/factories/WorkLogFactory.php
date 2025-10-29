<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkLogFactory extends Factory
{
    protected $model = WorkLog::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');
        $startTime = $this->faker->dateTimeBetween('-30 days', 'now');
        $endTime = Carbon::instance($startTime)->addHours($this->faker->numberBetween(1, 8));

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'date' => $date,
            'description' => $this->faker->sentence(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'billable' => $this->faker->boolean(80), // 80% billable
        ];
    }

    public function billable(): self
    {
        return $this->state(['billable' => true]);
    }

    public function nonBillable(): self
    {
        return $this->state(['billable' => false]);
    }

    public function active(): self
    {
        return $this->state(['end_time' => null]);
    }
}
