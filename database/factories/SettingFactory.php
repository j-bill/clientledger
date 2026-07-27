<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    private static int $counter = 0;

    public function definition(): array
    {
        return [
            'key' => 'setting_'.++self::$counter,
            'value' => $this->faker->sentence(),
        ];
    }
}
