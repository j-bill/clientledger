<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\WorkLog;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.de',
            'password' => bcrypt('adminadmin'),
            'role' => 'admin'
        ]);

        // Create freelancers with different hourly rates
        $freelancers = [
            User::create([
                'name' => 'Alex Thompson',
                'email' => 'alex@example.com',
                'password' => bcrypt('password'),
                'role' => 'freelancer',
                'hourly_rate' => 85
            ]),
            User::create([
                'name' => 'Sarah Chen',
                'email' => 'sarah@example.com',
                'password' => bcrypt('password'),
                'role' => 'freelancer',
                'hourly_rate' => 95
            ]),
            User::create([
                'name' => 'Marcus Rodriguez',
                'email' => 'marcus@example.com',
                'password' => bcrypt('password'),
                'role' => 'freelancer',
                'hourly_rate' => 110
            ]),
            User::create([
                'name' => 'Emma Wilson',
                'email' => 'emma@example.com',
                'password' => bcrypt('password'),
                'role' => 'freelancer',
                'hourly_rate' => 90
            ])
        ];

        // Create customers with higher rates
        $customers = [
            Customer::create([
                'name' => 'Acme Corp',
                'contact_person' => 'John Doe',
                'contact_email' => 'john@acme.com',
                'contact_phone' => '555-0123',
                'hourly_rate' => 180
            ]),
            Customer::create([
                'name' => 'TechStart Inc',
                'contact_person' => 'Jane Smith',
                'contact_email' => 'jane@techstart.com',
                'contact_phone' => '555-0124',
                'hourly_rate' => 200
            ]),
            Customer::create([
                'name' => 'Global Solutions',
                'contact_person' => 'Mike Johnson',
                'contact_email' => 'mike@global.com',
                'contact_phone' => '555-0125',
                'hourly_rate' => 220
            ])
        ];

        // Create projects for each customer
        $faker = \Faker\Factory::create();
        $projects = [];
        foreach ($customers as $customer) {
            // Create 3-5 projects per customer
            $numProjects = rand(3, 5);
            
            for ($i = 0; $i < $numProjects; $i++) {
                // 20% chance of no deadline for ongoing projects
                $deadline = rand(1, 100) <= 20 ? null : Carbon::now()->addDays(rand(30, 365));
                
                $project = Project::create([
                    'name' => $faker->catchPhrase(),
                    'description' => $faker->paragraph(),
                    'customer_id' => $customer->id,
                    'hourly_rate' => $customer->hourly_rate + rand(-20, 20), // Slight rate variation
                    'deadline' => $deadline
                ]);

                // Assign 1-2 random freelancers to each project
                $numFreelancers = rand(1, 2);
                $selectedFreelancers = $faker->randomElements($freelancers, $numFreelancers);
                
                foreach ($selectedFreelancers as $freelancer) {
                    $project->users()->attach($freelancer->id, [
                        'hourly_rate' => $freelancer->hourly_rate
                    ]);
                }

                $projects[] = $project;
            }
        }

        // Create work logs for the past 12 months
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths(12)->startOfMonth();
        $currentDate = $startDate->copy();

        while ($currentDate <= $now) {
            // Generate 4-8 work logs per week
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();
            
            $workLogsPerWeek = rand(4, 8);
            
            for ($i = 0; $i < $workLogsPerWeek; $i++) {
                $date = $weekStart->copy()->addDays(rand(0, 6));
                $hours = rand(2, 8);
                $project = $projects[array_rand($projects)];
                
                // Get a random freelancer assigned to this project
                $freelancer = $project->users()->inRandomOrder()->first();
                
                // 85% chance of being billable
                $isBillable = rand(1, 100) <= 85;
                
                WorkLog::create([
                    'project_id' => $project->id,
                    'user_id' => $freelancer->id,
                    'date' => $date,
                    'start_time' => '09:00',
                    'end_time' => (9 + $hours) . ':00',
                    'hours_worked' => $hours,
                    'description' => $faker->sentence(),
                    'billable' => $isBillable,
                    'hourly_rate' => $project->hourly_rate,
                    'user_hourly_rate' => $freelancer->hourly_rate
                ]);
            }
            
            $currentDate->addWeek();
        }

        // Create invoices for each month
        $currentDate = $startDate->copy();
        while ($currentDate <= $now) {
            foreach ($customers as $customer) {
                // Get all billable work logs for this customer in this month
                $workLogs = WorkLog::whereHas('project', function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                })
                ->where('billable', true)
                ->whereMonth('date', $currentDate->month)
                ->whereYear('date', $currentDate->year)
                ->get();

                if ($workLogs->isNotEmpty()) {
                    // Calculate total amount based on project rates
                    $totalAmount = $workLogs->sum(function ($workLog) {
                        return $workLog->hours_worked * $workLog->hourly_rate;
                    });

                    // Create invoice
                    $invoice = Invoice::create([
                        'invoice_number' => 'INV-' . strtoupper(uniqid()),
                        'customer_id' => $customer->id,
                        'due_date' => $currentDate->copy()->addMonth()->endOfMonth(),
                        'total_amount' => $totalAmount,
                        'status' => 'paid'
                    ]);

                    // Attach work logs to invoice
                    $invoice->workLogs()->attach($workLogs->pluck('id'));
                }
            }
            
            $currentDate->addMonth();
        }
    }
}
