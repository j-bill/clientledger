<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use Carbon\Carbon;

class ProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        $admin = User::where('role', 'admin')->first();
        $freelancers = User::where('role', 'freelancer')->get()->toArray();
        $customers = Customer::all();

        foreach ($customers as $customerId => $customer) {
            // Create 8-12 projects per customer for much more aggressive growth
            $numProjects = rand(8, 12);
            
            for ($i = 0; $i < $numProjects; $i++) {
                // Progressive deadline - earlier projects are more likely to be completed
                $dayOffset = $i * 30; // Spread projects more tightly
                $deadline = Carbon::now()->addDays($dayOffset + rand(30, 180));
                
                // Progressive rate increase - later projects have higher rates (more aggressive)
                $rateMultiplier = 1 + ($i * 0.12); // 0%, 12%, 24%, 36%...
                $hourlyRate = intval($customer->hourly_rate * $rateMultiplier);
                
                $project = Project::create([
                    'name' => $faker->catchPhrase() . ' - Phase ' . ($i + 1),
                    'description' => $faker->paragraph(3),
                    'customer_id' => $customer->id,
                    'hourly_rate' => $hourlyRate,
                    'deadline' => $deadline
                ]);

                // Assign 3-4 random freelancers to each project (more team members)
                $numFreelancers = rand(3, 4);
                $selectedFreelancers = $faker->randomElements($freelancers, min($numFreelancers, count($freelancers)));
                
                foreach ($selectedFreelancers as $freelancer) {
                    $project->users()->attach($freelancer['id'], [
                        'hourly_rate' => $freelancer['hourly_rate']
                    ]);
                }
                
                // 70% chance to also assign the admin to the project (more involvement)
                if (rand(1, 100) <= 70) {
                    $project->users()->attach($admin->id, [
                        'hourly_rate' => $admin->hourly_rate
                    ]);
                }
            }
        }
    }
}
