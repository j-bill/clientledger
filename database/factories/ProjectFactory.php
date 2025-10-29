<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->catchPhrase(),
            'customer_id' => Customer::factory(),
            'hourly_rate' => $this->faker->randomFloat(2, 25, 150),
        ];
    }
}
