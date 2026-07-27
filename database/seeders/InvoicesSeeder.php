<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WorkLog;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;

class InvoicesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create();

        // Create invoices for each COMPLETED month (not future months)
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths(12)->startOfMonth();
        $currentDate = $startDate->copy();

        $customers = Customer::all();

        // Guarantees the dashboard's revenue trend (which only counts PAID
        // invoices) climbs every month: any month whose paid total would dip
        // below the previous month's gets enough 'sent' invoices flipped to
        // 'paid' to close the gap.
        $previousPaidTotal = 0;

        while ($currentDate <= $now) {
            // Only create invoices for months that have ended
            $monthEnd = $currentDate->copy()->endOfMonth();
            if ($monthEnd->isAfter($now)) {
                // Skip months in the future
                $currentDate->addMonth();

                continue;
            }

            $monthInvoices = [];

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

                    // Invoices have had time to be paid by the time they're a
                    // couple of months old; only the most recent completed
                    // month still has a few genuinely outstanding.
                    $status = $invoiceDate->isAfter($now->copy()->subDays(45))
                        ? $faker->randomElement(['paid', 'paid', 'paid', 'sent'])
                        : 'paid';

                    // Create invoice with custom timestamps
                    $invoice = new Invoice([
                        'invoice_number' => 'INV-'.$currentDate->format('Ym').'-'.str_pad((string) $customer->id, 3, '0', STR_PAD_LEFT),
                        'customer_id' => $customer->id,
                        'issue_date' => $invoiceDate->toDateString(),
                        'due_date' => $currentDate->copy()->addMonth()->endOfMonth()->toDateString(),
                        'total_amount' => $totalAmount,
                        'status' => $status,
                        'notes' => $faker->optional(0.3)->sentence(),
                    ]);

                    // Set custom timestamps
                    $invoice->created_at = $invoiceDate;
                    $invoice->updated_at = $invoiceDate;
                    $invoice->save();

                    // Attach work logs to invoice
                    $invoice->workLogs()->attach($workLogs->pluck('id'));

                    $monthInvoices[] = $invoice;
                }
            }

            $paidTotal = collect($monthInvoices)
                ->where('status', 'paid')
                ->reduce(fn (float $carry, Invoice $invoice): float => $carry + (float) $invoice->total_amount, 0.0);
            $minRequired = $previousPaidTotal * 1.03;

            if ($paidTotal < $minRequired) {
                $flippable = collect($monthInvoices)
                    ->where('status', '!=', 'paid')
                    ->sortByDesc('total_amount');

                foreach ($flippable as $invoice) {
                    if ($paidTotal >= $minRequired) {
                        break;
                    }
                    $invoice->status = 'paid';
                    $invoice->save();
                    $paidTotal += (float) $invoice->total_amount;
                }
            }

            $previousPaidTotal = $paidTotal;
            $currentDate->addMonth();
        }
    }
}
