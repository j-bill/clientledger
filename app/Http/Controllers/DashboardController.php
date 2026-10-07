<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\WorkLog;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // Import Request
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get the database driver type
     */
    private function isUsingSQLite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    /**
     * Get year extraction SQL for current database driver
     *
     * @param  literal-string  $column
     * @return literal-string
     */
    private function yearExtract(string $column): string
    {
        return $this->isUsingSQLite()
            ? "cast(strftime('%Y', $column) as integer) as year"
            : "YEAR($column) as year";
    }

    /**
     * Get month extraction SQL for current database driver
     *
     * @param  literal-string  $column
     * @return literal-string
     */
    private function monthExtract(string $column): string
    {
        return $this->isUsingSQLite()
            ? "cast(strftime('%m', $column) as integer) as month"
            : "MONTH($column) as month";
    }

    /**
     * Get date extraction SQL for current database driver
     *
     * @param  literal-string  $column
     * @return literal-string
     */
    private function dateExtract(string $column): string
    {
        return $this->isUsingSQLite()
            ? "date($column) as date"
            : "DATE($column) as date";
    }

    /**
     * Year-month key ("2026-7") for a toBase() aggregate row.
     */
    private function monthKey(\stdClass $row): string
    {
        $year = is_numeric($row->year ?? null) ? (int) $row->year : 0;
        $month = is_numeric($row->month ?? null) ? (int) $row->month : 0;

        return $year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Numeric column of an aggregate row (or model), 0.0 when absent.
     */
    private function rowTotal(?object $row, string $column = 'total'): float
    {
        $value = $row->{$column} ?? null;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Date string column of a toBase() aggregate row.
     */
    private function rowDate(\stdClass $row): ?string
    {
        return is_string($row->date ?? null) ? $row->date : null;
    }

    /**
     * Admin revenue per month of *work*, split into paid and due.
     *
     * Billable work logs land in the month they were worked: paid once a paid
     * invoice carries them, due otherwise (open invoice or not invoiced yet).
     * Invoice amounts not backed by work logs (manual items, rate drift) land
     * in the invoice's issue month. Keying invoices by issue_date alone shifted
     * every month by one billing cycle: September's work, invoiced in October,
     * showed up as October revenue next to October's uninvoiced work.
     *
     * @return array<string, array{paid: float, due: float}> keyed "Y-m"
     */
    private function revenueByWorkMonth(Carbon $from, Carbon $to): array
    {
        $statuses = ['paid', 'sent', 'draft', 'overdue'];
        $months = [];
        $add = function (string $month, bool $paid, float $amount) use (&$months): void {
            $months[$month] ??= ['paid' => 0.0, 'due' => 0.0];
            $months[$month][$paid ? 'paid' : 'due'] += $amount;
        };

        $workLogs = WorkLog::where('billable', true)
            ->whereBetween('date', [$from, $to])
            ->with('invoices:id,status')
            ->get(['id', 'date', 'hours_worked', 'hourly_rate']);
        foreach ($workLogs as $log) {
            $add(
                Carbon::parse($log->date)->format('Y-m'),
                $log->invoices->contains(fn (Invoice $invoice) => $invoice->status === 'paid'),
                (float) $log->hours_worked * (float) $log->hourly_rate,
            );
        }

        $invoices = Invoice::whereIn('status', $statuses)
            ->whereBetween('issue_date', [$from, $to])
            ->with('workLogs:id,hours_worked,hourly_rate')
            ->get(['id', 'status', 'issue_date', 'total_amount']);
        foreach ($invoices as $invoice) {
            $logged = $invoice->workLogs->sum(fn (WorkLog $log) => (float) $log->hours_worked * (float) $log->hourly_rate);
            $rest = (float) $invoice->total_amount - $logged;
            if (abs($rest) >= 0.005) {
                $add(Carbon::parse($invoice->issue_date)->format('Y-m'), $invoice->status === 'paid', $rest);
            }
        }

        return $months;
    }

    /**
     * Paid + due summed over the months of $revenue that start with $prefix ("2026" or "2026-09").
     *
     * @param  array<string, array{paid: float, due: float}>  $revenue
     * @return array{paid: float, due: float}
     */
    private function revenueFor(array $revenue, string $prefix): array
    {
        $sum = ['paid' => 0.0, 'due' => 0.0];
        foreach ($revenue as $month => $amounts) {
            if (str_starts_with($month, $prefix)) {
                $sum['paid'] += $amounts['paid'];
                $sum['due'] += $amounts['due'];
            }
        }

        return ['paid' => round($sum['paid'], 2), 'due' => round($sum['due'], 2)];
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        $isAdmin = $user->isAdmin();

        $now = Carbon::now();
        $today = $now->copy()->endOfDay(); // Ensure we include today
        $lastYearStart = $now->copy()->subYear()->startOfYear();
        $lastYearEnd = $lastYearStart->copy()->endOfYear();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();

        // Rolling trailing-12-months window used only by the trend *charts*
        // (distinct from the "This Year" KPI card, which is calendar-year).
        $rollingYearStart = $now->copy()->subYear()->startOfMonth();

        // Extrapolation factors
        $daysInMonth = $now->daysInMonth;
        $currentDay = $now->day;
        $monthlyExtrapolationFactor = ($currentDay > 0 && $currentDay < $daysInMonth) ? $daysInMonth / $currentDay : 1; // Avoid extrapolation on day 1 or last day

        // Yearly extrapolation: based on how many months have passed
        $currentMonth = $now->month; // 1-12
        $yearlyExtrapolationFactor = ($currentMonth > 0 && $currentMonth < 12) ? 12 / $currentMonth : 1;

        // Initialize response data structure with defaults for all keys
        $responseData = [
            'kpis' => [
                'revenue' => [
                    'yearly' => ['actual' => 0, 'extrapolated' => 0],
                    'last_year' => ['paid' => 0, 'due' => 0],
                    'monthly' => ['actual' => 0, 'extrapolated' => 0],
                    'last_month' => ['paid' => 0, 'due' => 0],
                    'is_extrapolated' => false,
                ],
                'hours' => [
                    'yearly' => ['actual' => 0, 'extrapolated' => 0, 'actual_billable' => 0, 'extrapolated_billable' => 0],
                    'last_year' => ['total' => 0, 'billable' => 0],
                    'monthly' => ['actual' => 0, 'extrapolated' => 0, 'actual_billable' => 0, 'extrapolated_billable' => 0],
                    'last_month' => ['total' => 0, 'billable' => 0],
                ],
                'projects' => [
                    'active' => 0,
                    'overdue' => 0,
                ],
                'earnings' => [
                    'yearly' => ['actual' => 0, 'extrapolated' => 0],
                    'last_year' => ['paid' => 0, 'due' => 0],
                    'monthly' => ['actual' => 0, 'extrapolated' => 0],
                    'last_month' => ['paid' => 0, 'due' => 0],
                    'is_extrapolated' => false,
                ],
            ],
            'revenue_by_customer' => [],
            'yearly_revenue_trend' => [],
            'hero_trend_data' => [],
            'monthly_hours' => [],
            'upcoming_deadlines' => [],
            'earnings_by_project' => [],
            'yearly_earnings_trend' => [],
        ];

        // --- Base Queries --- (Filter by user if not admin)
        $workLogQueryBase = WorkLog::query();
        $projectQueryBase = Project::query();
        if (! $isAdmin) {
            $workLogQueryBase = $workLogQueryBase->where('user_id', $user->id);
            $projectQueryBase = $projectQueryBase->forUser($user);
        }

        // --- Hours KPIs (Common for both roles, but filtered) ---
        $yearlyHours = (clone $workLogQueryBase)
            ->whereYear('date', $now->year)
            ->sum('hours_worked');
        $yearlyHoursExtrapolated = $currentMonth > 0 && $currentMonth < 12
            ? number_format($yearlyHours * $yearlyExtrapolationFactor, 2, '.', '')
            : $yearlyHours;

        $lastYearHours = (clone $workLogQueryBase)
            ->whereBetween('date', [$lastYearStart, $lastYearEnd])
            ->sum('hours_worked');

        $currentMonthHours = (clone $workLogQueryBase)
            ->whereBetween('date', [$thisMonthStart, $now]) // Sum up to current day for extrapolation base
            ->sum('hours_worked');
        $monthlyHoursExtrapolated = $currentDay > 0 && $currentDay < $daysInMonth
            ? number_format($currentMonthHours * $monthlyExtrapolationFactor, 2, '.', '')
            : $currentMonthHours;

        $lastMonthHours = (clone $workLogQueryBase)
            ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
            ->sum('hours_worked');

        // --- Billable Hours KPIs (Common for both roles, but filtered) ---
        $billableWorkLogQuery = (clone $workLogQueryBase)->where('billable', true);
        $yearlyBillableHours = (clone $billableWorkLogQuery)
            ->whereYear('date', $now->year)
            ->sum('hours_worked');
        $yearlyBillableHoursExtrapolated = $currentMonth > 0 && $currentMonth < 12
            ? number_format($yearlyBillableHours * $yearlyExtrapolationFactor, 2, '.', '')
            : $yearlyBillableHours;

        $lastYearBillableHours = (clone $billableWorkLogQuery)
            ->whereBetween('date', [$lastYearStart, $lastYearEnd])
            ->sum('hours_worked');

        $currentMonthBillableHours = (clone $billableWorkLogQuery)
            ->whereBetween('date', [$thisMonthStart, $now])
            ->sum('hours_worked');
        $monthlyBillableHoursExtrapolated = $currentDay > 0 && $currentDay < $daysInMonth
            ? number_format($currentMonthBillableHours * $monthlyExtrapolationFactor, 2, '.', '')
            : $currentMonthBillableHours;

        $lastMonthBillableHours = (clone $billableWorkLogQuery)
            ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
            ->sum('hours_worked');

        // Assign common hours KPIs
        $responseData['kpis']['hours'] = [
            'yearly' => [
                'actual' => $yearlyHours,
                'extrapolated' => $yearlyHoursExtrapolated,
                'actual_billable' => $yearlyBillableHours,
                'extrapolated_billable' => $yearlyBillableHoursExtrapolated,
            ],
            'last_year' => [
                'total' => $lastYearHours,
                'billable' => $lastYearBillableHours,
            ],
            'monthly' => [
                'actual' => $currentMonthHours,
                'extrapolated' => $monthlyHoursExtrapolated,
                'actual_billable' => $currentMonthBillableHours,
                'extrapolated_billable' => $monthlyBillableHoursExtrapolated,
            ],
            'last_month' => [
                'total' => $lastMonthHours,
                'billable' => $lastMonthBillableHours,
            ],
        ];

        // --- Project KPIs (Common for both roles, but filtered) ---
        $activeProjects = (clone $projectQueryBase)
            ->where(function ($query) use ($now) {
                $query->whereNull('deadline')
                    ->orWhere('deadline', '>', $now);
            })
            ->count();
        $overdueProjects = (clone $projectQueryBase)
            ->where('deadline', '<', $now)
            ->whereNotNull('deadline')
            ->count();

        // Assign common project KPIs
        $responseData['kpis']['projects'] = [
            'active' => $activeProjects,
            'overdue' => $overdueProjects,
        ];

        // --- Upcoming Deadlines (Common for both roles, but filtered) ---
        $upcomingDeadlines = (clone $projectQueryBase)
            ->with('customer') // Eager load customer
            ->whereNotNull('deadline')
            ->where('deadline', '>', $now)
            ->orderBy('deadline')
            ->limit(6)
            ->get()
            ->map(function ($project) use ($now) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'customer' => $project->customer->name ?? 'N/A',
                    'deadline' => $project->deadline?->format('Y-m-d'),
                    'days_until' => $now->diffInDays($project->deadline, false),
                ];
            });
        $responseData['upcoming_deadlines'] = $upcomingDeadlines;

        // --- Role-Specific KPIs & Data ---
        if ($isAdmin) {
            // --- Admin: Revenue KPIs ---

            // Every revenue figure counts work in the month it was done,
            // not in the month its invoice was issued (revenueByWorkMonth).
            $revenue = $this->revenueByWorkMonth($lastYearStart, $today);

            // THIS MONTH: Actual + Extrapolated
            $thisMonth = $this->revenueFor($revenue, $now->format('Y-m'));
            $currentMonthActual = $thisMonth['paid'] + $thisMonth['due'];
            $currentMonthExtrapolated = $currentMonthActual * $monthlyExtrapolationFactor;

            // LAST MONTH: Paid + Due (open invoices and uninvoiced work)
            $lastMonth = $this->revenueFor($revenue, $lastMonthStart->format('Y-m'));
            $lastMonthPaid = $lastMonth['paid'];
            $lastMonthDue = $lastMonth['due'];

            // THIS YEAR: Actual + Extrapolated estimate
            $thisYear = $this->revenueFor($revenue, $now->format('Y'));
            $thisYearActual = $thisYear['paid'] + $thisYear['due'];
            $thisYearExtrapolated = $thisYearActual * $yearlyExtrapolationFactor;

            // LAST YEAR: Paid + Due
            $lastYear = $this->revenueFor($revenue, $lastYearStart->format('Y'));
            $lastYearPaid = $lastYear['paid'];
            $lastYearDue = $lastYear['due'];

            // --- Admin: Revenue by Customer ---
            $revenueByCustomer = Invoice::with('customer')
                ->whereIn('status', ['paid', 'sent', 'draft'])
                ->get()
                ->groupBy('customer.name')
                ->map(function ($invoices) {
                    return $invoices->sum('total_amount');
                })
                ->sortDesc();

            // --- Admin: Revenue Trend (PAID) + Hero Trend (PAID + DUE), LAST 12 MONTHS ---
            $yearlyRevenueTrend = [];
            $heroTrendData = [];
            $startDate = $rollingYearStart->copy();
            while ($startDate <= $now) {
                $month = $this->revenueFor($revenue, $startDate->format('Y-m'));
                if ($month['paid'] != 0) {
                    $yearlyRevenueTrend[] = [
                        'date' => $startDate->format('Y-m-01'),
                        'amount' => $month['paid'],
                    ];
                }
                $heroTrendData[] = [
                    'date' => $startDate->format('Y-m-01'),
                    'amount' => $month['paid'] + $month['due'],
                ];

                $startDate->addMonth();
            }

            // --- Admin: Monthly Hours Worked Trend (All Users) ---
            $monthlyHoursData = WorkLog::whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->selectRaw($this->dateExtract('date').', SUM(hours_worked) as total, SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable')
                ->groupBy('date')
                ->orderBy('date')
                ->toBase()
                ->get()
                ->keyBy(fn ($item) => Carbon::parse($this->rowDate($item))->format('Y-m-d'));

            $period = CarbonPeriod::create($thisMonthStart, $today);
            $monthlyHoursWorked = collect($period->toArray())->map(function ($date) use ($monthlyHoursData) {
                $dateString = $date->format('Y-m-d');
                $data = $monthlyHoursData->get($dateString);

                return [
                    'date' => $dateString,
                    'hours' => number_format($this->rowTotal($data), 2, '.', ''),
                    'billable' => number_format($this->rowTotal($data, 'billable'), 2, '.', ''),
                ];
            })->values(); // Convert back to simple array

            // Populate Admin Response Data (overwriting defaults)
            $responseData['kpis']['revenue'] = [
                'yearly' => [
                    'actual' => $thisYearActual,
                    'extrapolated' => floatval(number_format($thisYearExtrapolated, 2, '.', '')),
                ],
                'last_year' => [
                    'paid' => $lastYearPaid,
                    'due' => $lastYearDue,
                ],
                'monthly' => [
                    'actual' => floatval(number_format($currentMonthActual, 2, '.', '')),
                    'extrapolated' => floatval(number_format($currentMonthExtrapolated, 2, '.', '')),
                ],
                'last_month' => [
                    'paid' => $lastMonthPaid,
                    'due' => $lastMonthDue,
                ],
                'is_extrapolated' => ($monthlyExtrapolationFactor > 1 || $yearlyExtrapolationFactor > 1),
            ];
            $responseData['monthly_hours'] = $monthlyHoursWorked; // Admin sees all hours trend
            $responseData['revenue_by_customer'] = $revenueByCustomer; // Assign revenue by customer
            $responseData['yearly_revenue_trend'] = $yearlyRevenueTrend; // Assign yearly revenue trend (paid only)
            $responseData['hero_trend_data'] = $heroTrendData; // Assign hero trend (all invoices + work logs)

        } else {
            // --- Non-Admin: Earnings KPIs ---
            $yearlyEarnings = (clone $workLogQueryBase)
                ->whereYear('date', $now->year)
                ->sum(DB::raw('hours_worked * user_hourly_rate')); // Calculate sum
            $lastYearEarnings = (clone $workLogQueryBase)
                ->whereBetween('date', [$lastYearStart, $lastYearEnd])
                ->sum(DB::raw('hours_worked * user_hourly_rate')); // Calculate sum
            $currentMonthEarnings = (clone $workLogQueryBase)
                ->whereBetween('date', [$thisMonthStart, $now])
                ->sum(DB::raw('hours_worked * user_hourly_rate')); // Calculate sum
            $lastMonthEarnings = (clone $workLogQueryBase)
                ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
                ->sum(DB::raw('hours_worked * user_hourly_rate')); // Calculate sum

            // --- Non-Admin: Earnings by Project ---
            $earningsByProject = (clone $workLogQueryBase)
                ->with('project') // Eager load project
                // Use DB::raw for calculated sum
                ->selectRaw('project_id, SUM(hours_worked * user_hourly_rate) as total_earnings')
                ->whereNotNull('project_id')
                ->groupBy('project_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    // Use project name as key, handle potential null project
                    $projectName = $item->project ? $item->project->name : 'Unknown Project';

                    // Ensure total_earnings is treated as a number
                    return [$projectName => number_format($this->rowTotal($item, 'total_earnings'), 2, '.', '')];
                })
                ->sortDesc();

            // --- Non-Admin: Monthly Earnings Trend (rolling last 12 months) ---
            $monthlyEarningsData = (clone $workLogQueryBase)
                ->where('date', '>=', $rollingYearStart)
                ->selectRaw($this->yearExtract('date').', '.$this->monthExtract('date').', SUM(hours_worked * user_hourly_rate) as total')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->toBase()
                ->get()
                ->keyBy(fn ($item) => $this->monthKey($item));

            // Create a collection of the trailing 12 months up to the current month
            $yearlyEarningsTrend = collect();
            $trendMonth = $rollingYearStart->copy();
            while ($trendMonth <= $now) {
                $monthKey = $trendMonth->format('Y-m');
                $monthData = $monthlyEarningsData->get($monthKey);

                $yearlyEarningsTrend->push([
                    'date' => $monthKey,
                    'amount' => number_format($this->rowTotal($monthData), 2, '.', ''),
                ]);

                $trendMonth->addMonth();
            }

            // --- Non-Admin: Monthly Hours Worked Trend (Own Hours) ---
            $monthlyHoursData = (clone $workLogQueryBase) // Already filtered for user
                ->whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->selectRaw($this->dateExtract('date').', SUM(hours_worked) as total, SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable')
                ->groupBy('date')
                ->orderBy('date')
                ->toBase()
                ->get()
                ->keyBy(fn ($item) => Carbon::parse($this->rowDate($item))->format('Y-m-d'));

            $monthPeriod = CarbonPeriod::create($thisMonthStart, $today);
            $monthlyHoursWorked = collect($monthPeriod->toArray())->map(function ($date) use ($monthlyHoursData) {
                $dateString = $date->format('Y-m-d');
                $data = $monthlyHoursData->get($dateString);

                return [
                    'date' => $dateString,
                    'hours' => number_format($this->rowTotal($data), 2, '.', ''),
                    'billable' => number_format($this->rowTotal($data, 'billable'), 2, '.', ''),
                ];
            })->values(); // Convert back to simple array

            // Populate Non-Admin Response Data (overwriting defaults)
            $responseData['kpis']['earnings'] = [
                'yearly' => [
                    'actual' => floatval($yearlyEarnings),
                    'extrapolated' => 0, // Freelancer views years as-is, no extrapolation for full years
                ],
                'last_year' => [
                    'paid' => floatval($lastYearEarnings), // All last year earnings are "earned"
                    'due' => 0, // No unpaid earnings for freelancer
                ],
                'monthly' => [
                    'actual' => floatval($currentMonthEarnings),
                    'extrapolated' => floatval(number_format($currentMonthEarnings * $monthlyExtrapolationFactor, 2, '.', '')),
                ],
                'last_month' => [
                    'paid' => floatval($lastMonthEarnings), // All last month earnings are "earned"
                    'due' => 0, // No unpaid earnings for freelancer
                ],
                'is_extrapolated' => ($monthlyExtrapolationFactor > 1),
            ];
            $responseData['monthly_hours'] = $monthlyHoursWorked; // Non-admin sees own hours trend
            $responseData['earnings_by_project'] = $earningsByProject; // Assign earnings by project
            $responseData['yearly_earnings_trend'] = $yearlyEarningsTrend; // Assign yearly earnings trend
            $responseData['hero_trend_data'] = $yearlyEarningsTrend; // Use same for hero trend
        }

        return response()->json($responseData);
    }
}
