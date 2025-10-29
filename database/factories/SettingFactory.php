<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        static $counter = 0;
        
        return [
            'key' => 'setting_' . ++$counter,
            'value' => $this->faker->sentence(),
        ];
    }
}
