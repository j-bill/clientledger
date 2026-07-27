<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => 'Apt. '.fake()->buildingNumber(),
            'city' => fake()->city(),
            'state' => fake()->city(),
            'postcode' => fake()->postcode(),
            'country' => fake()->country(),
            'vat_number' => fake()->bothify('??########'),
            'hourly_rate' => fake()->randomFloat(2, 50, 200),
        ];
    }
}
