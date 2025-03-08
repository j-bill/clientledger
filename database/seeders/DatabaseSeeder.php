<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\WorkLog;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('email', 'admin@admin.de')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'admin@admin.de',
                'role' => 'admin',
                'password' => bcrypt('adminadmin'),
            ]);
        }

        $faker = Faker::create();

        $amountCustomers = 10;
        $amountProjects = 5;
        $amountWorklogs = 200;

        // Create customers
        for ($i = 0; $i < $amountCustomers; $i++) {
            Customer::create([
                'name' => $faker->company,
                'contact_person' => $faker->name,
                'contact_email' => $faker->email,
                'contact_phone' => $faker->phoneNumber,
                'address_line_1' => $faker->streetAddress,
                'address_line_2' => $faker->secondaryAddress,
                'city' => $faker->city,
                'state' => $faker->state,
                'postcode' => $faker->postcode,
                'country' => $faker->country,
                'vat_number' => $faker->randomNumber(8),
                'hourly_rate' => $faker->randomFloat(2, 50, 150),
            ]);
        }

        // Create projects
        $customerIds = Customer::pluck('id')->toArray();
        for ($j = 0; $j < $amountProjects; $j++) {
            Project::create([
                'name' => $faker->sentence(3),
                'description' => $faker->paragraph,
                'customer_id' => $faker->randomElement($customerIds),
                'hourly_rate' => $faker->randomFloat(2, 50, 150),
                'deadline' => $faker->dateTimeBetween('+1 month', '+6 months'),
            ]);
        }

        // Create worklogs
        $projectIds = Project::pluck('id')->toArray();
        for ($k = 0; $k < $amountWorklogs; $k++) {
            $startTime = $faker->dateTimeThisYear;
            $endTime = (clone $startTime)->modify('+'.rand(1, 8).' hours');

            WorkLog::create([
                'project_id' => $faker->randomElement($projectIds),
                'date' => $startTime->format('Y-m-d'),
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'hours_worked' => $endTime->diff($startTime)->h,
                'description' => $faker->sentence,
                'billable' => $faker->boolean,
            ]);
        }
    }
}
