<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WorkLog;
use Carbon\Carbon;

class InvoicesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Create invoices for each COMPLETED month (not future months)
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths(12)->startOfMonth();
        $currentDate = $startDate->copy();

        $customers = Customer::all();

        while ($currentDate <= $now) {
            // Only create invoices for months that have ended
            $monthEnd = $currentDate->copy()->endOfMonth();
            if ($monthEnd->isAfter($now)) {
                // Skip months in the future
                $currentDate->addMonth();
                continue;
            }

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

                    // Create invoice at the end of the month for work done that month
                    $invoiceDate = $currentDate->copy()->endOfMonth();
                    
                    // Determine status based on how far in the past the invoice is
                    if ($invoiceDate->isAfter($now->copy()->subDays(60))) {
                        // Recent invoices (last 2 months) are more likely to be sent
                        $status = $faker->randomElement(['sent', 'paid', 'sent']);
                    } else {
                        // Older invoices should mostly be paid
                        $status = $faker->randomElement(['paid', 'paid', 'paid', 'sent']);
                    }

                    // Create invoice with custom timestamps
                    $invoice = new Invoice([
                        'invoice_number' => 'INV-' . $currentDate->format('Ym') . '-' . str_pad($customer->id, 3, '0', STR_PAD_LEFT),
                        'customer_id' => $customer->id,
                        'issue_date' => $invoiceDate->toDateString(),
                        'due_date' => $currentDate->copy()->addMonth()->endOfMonth()->toDateString(),
                        'total_amount' => $totalAmount,
                        'status' => $status,
                        'notes' => $faker->optional(0.3)->sentence()
                    ]);
                    
                    // Set custom timestamps
                    $invoice->created_at = $invoiceDate;
                    $invoice->updated_at = $invoiceDate;
                    $invoice->save();

                    // Attach work logs to invoice
                    $invoice->workLogs()->attach($workLogs->pluck('id'));
                }
            }
            
            $currentDate->addMonth();
        }
    }
}
