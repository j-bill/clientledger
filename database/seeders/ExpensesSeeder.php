<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Expense;
use Carbon\Carbon;

class ExpensesSeeder extends Seeder
{
    private array $categories = [
        'Software' => [
            ['desc' => 'Annual subscription renewal for project management tooling', 'range' => [200, 900]],
            ['desc' => 'Cloud hosting and infrastructure costs', 'range' => [80, 450]],
            ['desc' => 'Design and prototyping software license', 'range' => [50, 300]],
        ],
        'Hardware' => [
            ['desc' => 'Replacement laptop for development work', 'range' => [800, 2200]],
            ['desc' => 'External monitor and docking station', 'range' => [150, 600]],
            ['desc' => 'Office network equipment upgrade', 'range' => [100, 400]],
        ],
        'Travel' => [
            ['desc' => 'Client site visit — flights and accommodation', 'range' => [250, 1200]],
            ['desc' => 'Train tickets for on-site meeting', 'range' => [40, 220]],
            ['desc' => 'Mileage reimbursement for local client visits', 'range' => [30, 150]],
        ],
        'Office Supplies' => [
            ['desc' => 'General office and stationery supplies', 'range' => [20, 120]],
            ['desc' => 'Ergonomic desk equipment', 'range' => [60, 350]],
        ],
        'Marketing' => [
            ['desc' => 'Sponsored ads for lead generation campaign', 'range' => [150, 800]],
            ['desc' => 'Business cards and branded materials', 'range' => [40, 200]],
        ],
        'Professional Services' => [
            ['desc' => 'Accounting and bookkeeping services', 'range' => [150, 500]],
            ['desc' => 'Legal review of client contract', 'range' => [200, 900]],
        ],
    ];

    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $now = Carbon::now();

        $customers = Customer::all();
        $projects = Project::all();
        $categoryNames = array_keys($this->categories);

        // Same trailing window as WorkLogsSeeder/InvoicesSeeder (12 completed
        // months + the current partial one), with expense volume growing in
        // step with the business — busier months rack up more costs too.
        $monthOffsets = range(12, 0);

        foreach ($monthOffsets as $index => $monthsAgo) {
            $monthStart = $now->copy()->subMonths($monthsAgo)->startOfMonth();
            $isCurrentMonth = $monthStart->isSameMonth($now);
            $monthEnd = $isCurrentMonth ? $now->copy() : $monthStart->copy()->endOfMonth();
            $daySpan = max($monthStart->diffInDays($monthEnd), 1);

            $expensesThisMonth = (int) round(4 + $index * 0.6); // ~4 early on, ~11-12 by now

            for ($n = 0; $n < $expensesThisMonth; $n++) {
                $categoryName = $categoryNames[array_rand($categoryNames)];
                $entry = $this->categories[$categoryName][array_rand($this->categories[$categoryName])];
                [$min, $max] = $entry['range'];

                $date = $monthStart->copy()->addDays(rand(0, $daySpan));

                // Most expenses are tied to a project/customer, a few are
                // general overhead with no association.
                $project = rand(1, 100) <= 75 && $projects->isNotEmpty() ? $projects->random() : null;
                $customer = $project?->customer_id
                    ? $customers->firstWhere('id', $project->customer_id)
                    : ($customers->isNotEmpty() && rand(1, 100) <= 30 ? $customers->random() : null);

                Expense::create([
                    'customer_id' => $customer?->id,
                    'project_id' => $project?->id,
                    'description' => $entry['desc'],
                    'amount' => $faker->randomFloat(2, $min, $max),
                    'currency' => 'EUR',
                    'date' => $date->toDateString(),
                    'category' => $categoryName,
                    'is_tax_deductible' => rand(1, 100) <= 85,
                ]);
            }
        }
    }
}
