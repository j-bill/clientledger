<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'admin@admin.de',
            'role' => 'admin',
            'password' => bcrypt('adminadmin'),
        ]);

        $customers = [
            [
                'name' => 'Acme Corp',
                'contact_person' => 'John Doe',
                'contact_email' => 'john@acme.com',
                'contact_phone' => '+49 176 12345678',
                'address_line_1' => 'Hauptstraße 1',
                'address_line_2' => '3rd Floor',
                'city' => 'Berlin',
                'state' => 'Berlin',
                'postcode' => '10115',
                'country' => 'Germany',
                'vat_number' => 'DE123456789',
                'hourly_rate' => 85.00,
            ],
            [
                'name' => 'Tech Solutions GmbH',
                'contact_person' => 'Jane Smith',
                'contact_email' => 'jane@techsolutions.com',
                'contact_phone' => '+49 30 987654321',
                'address_line_1' => 'Alexanderplatz 5',
                'address_line_2' => 'Building B',
                'city' => 'Berlin',
                'state' => 'Berlin',
                'postcode' => '10178',
                'country' => 'Germany',
                'vat_number' => 'DE987654321',
                'hourly_rate' => 95.00,
            ],
            [
                'name' => 'Digital Dynamics AG',
                'contact_person' => 'Maria Schmidt',
                'contact_email' => 'maria@digitaldynamics.de',
                'contact_phone' => '+49 89 11223344',
                'address_line_1' => 'Maximilianstraße 25',
                'city' => 'Munich',
                'state' => 'Bavaria',
                'postcode' => '80539',
                'country' => 'Germany',
                'vat_number' => 'DE456789012',
                'hourly_rate' => 120.00,
            ],
        ];

        $projectTemplates = [
            [
                'name' => 'E-Commerce Platform Revamp',
                'description' => 'Complete overhaul of the existing online shop including migration to Shopware 6, custom theme development, and integration of ERP system. Implementation of advanced analytics and personalization features.',
                'deadline_months' => 4,
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Development of a cross-platform mobile application using Flutter. Features include user authentication, push notifications, offline capability, and integration with REST APIs.',
                'deadline_months' => 3,
            ],
            [
                'name' => 'Corporate Website Redesign',
                'description' => 'Modern website redesign with focus on performance and SEO. Including content management system, blog section, and multilingual support.',
                'deadline_months' => 2,
            ],
            [
                'name' => 'Internal Dashboard',
                'description' => 'Development of an internal analytics dashboard using Laravel and Vue.js. Real-time data visualization, PDF report generation, and role-based access control.',
                'deadline_months' => 2,
            ],
            [
                'name' => 'API Development',
                'description' => 'Design and implementation of RESTful APIs for existing systems. Including authentication, rate limiting, and comprehensive documentation.',
                'deadline_months' => 3,
            ],
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::create($customerData);
            
            // Randomly select 2-3 projects for each customer
            $selectedProjects = collect($projectTemplates)
                ->random(rand(2, 3))
                ->each(function ($project) use ($customer) {
                    Project::create([
                        'name' => $project['name'],
                        'description' => $project['description'],
                        'customer_id' => $customer->id,
                        'hourly_rate' => $customer->hourly_rate,
                        'deadline' => now()->addMonths($project['deadline_months'])->addDays(rand(-5, 5)),
                    ]);
                });
        }
    }
}
