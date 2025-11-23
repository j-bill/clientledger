<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WorkLog;
use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request; // Import Request
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Carbon\CarbonPeriod;

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
     */
    private function yearExtract(string $column): string
    {
        return $this->isUsingSQLite()
            ? "cast(strftime('%Y', $column) as integer) as year"
            : "YEAR($column) as year";
    }

    /**
     * Get month extraction SQL for current database driver
     */
    private function monthExtract(string $column): string
    {
        return $this->isUsingSQLite()
            ? "cast(strftime('%m', $column) as integer) as month"
            : "MONTH($column) as month";
    }

    /**
     * Get date extraction SQL for current database driver
     */
    private function dateExtract(string $column): string
    {
        return $this->isUsingSQLite()
            ? "date($column) as date"
            : "DATE($column) as date";
    }

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        $isAdmin = $user->isAdmin();

        $now = Carbon::now();
        $today = $now->copy()->endOfDay(); // Ensure we include today
        $thisYearStart = $now->copy()->startOfYear();
        $lastYearStart = $now->copy()->subYear()->startOfYear();
        $lastYearEnd = $lastYearStart->copy()->endOfYear();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();

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
                    'is_extrapolated' => false
                ],
                'hours' => [
                    'yearly' => ['actual' => 0, 'extrapolated' => 0, 'actual_billable' => 0, 'extrapolated_billable' => 0],
                    'last_year' => ['total' => 0, 'billable' => 0],
                    'monthly' => ['actual' => 0, 'extrapolated' => 0, 'actual_billable' => 0, 'extrapolated_billable' => 0],
                    'last_month' => ['total' => 0, 'billable' => 0]
                ],
                'projects' => [
                    'active' => 0,
                    'overdue' => 0
                ],
                'earnings' => [
                    'yearly' => ['actual' => 0, 'extrapolated' => 0],
                    'last_year' => ['paid' => 0, 'due' => 0],
                    'monthly' => ['actual' => 0, 'extrapolated' => 0],
                    'last_month' => ['paid' => 0, 'due' => 0],
                    'is_extrapolated' => false
                ]
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
        if (!$isAdmin) {
            $workLogQueryBase = $workLogQueryBase->where('user_id', $user->id);
            // Use the scopeForUser method for projects
            if (method_exists(Project::class, 'scopeForUser')) {
                $projectQueryBase = $projectQueryBase->forUser($user);
            } else {
                $projectQueryBase = $projectQueryBase->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
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
                'extrapolated_billable' => $yearlyBillableHoursExtrapolated
            ],
            'last_year' => [
                'total' => $lastYearHours,
                'billable' => $lastYearBillableHours
            ],
            'monthly' => [
                'actual' => $currentMonthHours,
                'extrapolated' => $monthlyHoursExtrapolated,
                'actual_billable' => $currentMonthBillableHours,
                'extrapolated_billable' => $monthlyBillableHoursExtrapolated
            ],
            'last_month' => [
                'total' => $lastMonthHours,
                'billable' => $lastMonthBillableHours
            ]
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
            'overdue' => $overdueProjects
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
                    'deadline' => $project->deadline->format('Y-m-d'),
                    'days_until' => $now->diffInDays($project->deadline, false)
                ];
            });
        $responseData['upcoming_deadlines'] = $upcomingDeadlines;


        // --- Role-Specific KPIs & Data ---
        if ($isAdmin) {
            // --- Admin: Revenue KPIs ---
            
            // THIS MONTH: Actual from work logs + Extrapolated
            $currentMonthActual = WorkLog::where('billable', true)
                ->whereBetween('date', [$thisMonthStart, $now])
                ->sum(DB::raw('hours_worked * hourly_rate'));
            $currentMonthExtrapolated = $currentMonthActual * $monthlyExtrapolationFactor;
            
            // LAST MONTH: Paid invoices + Due invoices + Uninvoiced work logs
            $lastMonthPaid = Invoice::where('status', 'paid')
                ->whereBetween('issue_date', [$lastMonthStart, $lastMonthEnd])
                ->sum('total_amount');
            $lastMonthDue = Invoice::whereIn('status', ['sent', 'draft'])
                ->whereBetween('issue_date', [$lastMonthStart, $lastMonthEnd])
                ->sum('total_amount');
            // Include uninvoiced work logs valued at their hourly rate
            $lastMonthUninvoiced = WorkLog::where('billable', true)
                ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
                ->sum(DB::raw('hours_worked * hourly_rate'));
            $lastMonthDue += $lastMonthUninvoiced;
            
            // THIS YEAR: All issued invoices + Extrapolated estimate
            $thisYearActual = Invoice::whereIn('status', ['paid', 'sent', 'draft'])
                ->whereYear('issue_date', $now->year)
                ->sum('total_amount');
            $thisYearExtrapolated = $thisYearActual * $yearlyExtrapolationFactor;
            
            // LAST YEAR: Paid + Due
            $lastYearPaid = Invoice::where('status', 'paid')
                ->whereBetween('issue_date', [$lastYearStart, $lastYearEnd])
                ->sum('total_amount');
            $lastYearDue = Invoice::whereIn('status', ['sent', 'draft'])
                ->whereBetween('issue_date', [$lastYearStart, $lastYearEnd])
                ->sum('total_amount');

            // --- Admin: Revenue by Customer ---
            $revenueByCustomer = Invoice::with('customer')
                ->whereIn('status', ['paid', 'sent', 'draft'])
                ->get()
                ->groupBy('customer.name')
                ->map(function ($invoices) {
                    return $invoices->sum('total_amount');
                })
                ->sortDesc();

            // --- Admin: Yearly Revenue Trend (PAID INVOICES ONLY) ---
            $yearlyRevenueTrend = Invoice::where('status', 'paid')
                ->whereYear('issue_date', $now->year)
                ->get()
                ->groupBy(function ($invoice) {
                    return $invoice->issue_date->format('Y-m');
                })
                ->map(function ($group) {
                    return [
                        'date' => $group->first()->issue_date->format('Y-m'),
                        'amount' => $group->sum('total_amount')
                    ];
                })
                ->values();

            // --- Admin: Hero Trend (ALL INVOICES + UNINVOICED WORK) ---
            // Combine all invoices (paid, sent, draft) with uninvoiced work logs valued at project/customer rates
            $heroTrendData = [];
            
            // Get all invoices by month
            $allInvoices = Invoice::whereIn('status', ['paid', 'sent', 'draft'])
                ->whereYear('issue_date', $now->year)
                ->selectRaw($this->yearExtract('issue_date') . ', ' . $this->monthExtract('issue_date') . ', SUM(total_amount) as total')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                });

            // Get all work logs by month, valued at their hourly rate
            $allWorkLogs = WorkLog::where('billable', true)
                ->whereDoesntHave('invoices', function ($query) {
                    $query->whereIn('status', ['paid', 'sent', 'draft']);
                })
                ->whereYear('date', $now->year)
                ->selectRaw($this->yearExtract('date') . ', ' . $this->monthExtract('date') . ', SUM(hours_worked * hourly_rate) as total')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                });

            // Merge invoices and work logs by month
            // Iterate through all months of the current year to ensure no gaps
            for ($m = 1; $m <= 12; $m++) {
                $monthKey = $now->year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $invoiceAmount = $allInvoices->get($monthKey)?->total ?? 0;
                $workLogAmount = $allWorkLogs->get($monthKey)?->total ?? 0;
                
                $heroTrendData[] = [
                    'date' => Carbon::createFromDate($now->year, $m, 1)->format('Y-m'),
                    'amount' => $invoiceAmount + $workLogAmount
                ];
            }

            // --- Admin: Monthly Hours Worked Trend (All Users) ---
            $monthlyHoursData = WorkLog::whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->selectRaw($this->dateExtract('date') . ', SUM(hours_worked) as total, SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->date)->format('Y-m-d'); // Key by date string
                });

            $period = CarbonPeriod::create($thisMonthStart, $today);
            $monthlyHoursWorked = collect($period)->map(function ($date) use ($monthlyHoursData) {
                $dateString = $date->format('Y-m-d');
                $data = $monthlyHoursData->get($dateString);
                return [
                    'date' => $dateString,
                    'hours' => number_format($data->total ?? 0, 2, '.', ''),
                    'billable' => number_format($data->billable ?? 0, 2, '.', '')
                ];
            })->values(); // Convert back to simple array

            // Populate Admin Response Data (overwriting defaults)
            $responseData['kpis']['revenue'] = [
                'yearly' => [
                    'actual' => $thisYearActual,
                    'extrapolated' => floatval(number_format($thisYearExtrapolated, 2, '.', ''))
                ],
                'last_year' => [
                    'paid' => $lastYearPaid,
                    'due' => $lastYearDue
                ],
                'monthly' => [
                    'actual' => floatval(number_format($currentMonthActual, 2, '.', '')),
                    'extrapolated' => floatval(number_format($currentMonthExtrapolated, 2, '.', ''))
                ],
                'last_month' => [
                    'paid' => $lastMonthPaid,
                    'due' => $lastMonthDue
                ],
                'is_extrapolated' => ($monthlyExtrapolationFactor > 1 || $yearlyExtrapolationFactor > 1)
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
            $monthlyEarnings = number_format($currentMonthEarnings * $monthlyExtrapolationFactor, 2, '.', '');
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
                    return [$projectName => number_format($item->total_earnings ?? 0, 2, '.', '')];
                })
                ->sortDesc();

            // --- Non-Admin: Monthly Earnings Trend ---
            $monthlyEarningsData = (clone $workLogQueryBase)
                ->whereYear('date', $now->year)
                ->selectRaw($this->yearExtract('date') . ', ' . $this->monthExtract('date') . ', SUM(hours_worked * user_hourly_rate) as total')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    // Key by year-month
                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                });

            // Create a collection of all months in the current year up to current month
            $yearlyEarningsTrend = collect();
            for ($month = 1; $month <= $now->month; $month++) {
                $monthKey = $now->year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
                $monthData = $monthlyEarningsData->get($monthKey);
                $formattedDate = Carbon::createFromDate($now->year, $month, 1)->format('Y-m');

                $yearlyEarningsTrend->push([
                    'date' => $formattedDate,
                    'amount' => number_format($monthData->total ?? 0, 2, '.', '')
                ]);
            }

            // --- Non-Admin: Monthly Hours Worked Trend (Own Hours) ---
             $monthlyHoursData = (clone $workLogQueryBase) // Already filtered for user
                ->whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->selectRaw($this->dateExtract('date') . ', SUM(hours_worked) as total, SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->date)->format('Y-m-d'); // Key by date string
                });

            $monthPeriod = CarbonPeriod::create($thisMonthStart, $today);
            $monthlyHoursWorked = collect($monthPeriod)->map(function ($date) use ($monthlyHoursData) {
                $dateString = $date->format('Y-m-d');
                $data = $monthlyHoursData->get($dateString);
                return [
                    'date' => $dateString,
                    'hours' => number_format($data->total ?? 0, 2, '.', ''),
                    'billable' => number_format($data->billable ?? 0, 2, '.', '')
                ];
            })->values(); // Convert back to simple array

            // Populate Non-Admin Response Data (overwriting defaults)
            $responseData['kpis']['earnings'] = [
                'yearly' => [
                    'actual' => floatval($yearlyEarnings),
                    'extrapolated' => 0 // Freelancer views years as-is, no extrapolation for full years
                ],
                'last_year' => [
                    'paid' => floatval($lastYearEarnings), // All last year earnings are "earned"
                    'due' => 0 // No unpaid earnings for freelancer
                ],
                'monthly' => [
                    'actual' => floatval($currentMonthEarnings),
                    'extrapolated' => floatval(number_format($currentMonthEarnings * $monthlyExtrapolationFactor, 2, '.', ''))
                ],
                'last_month' => [
                    'paid' => floatval($lastMonthEarnings), // All last month earnings are "earned"
                    'due' => 0 // No unpaid earnings for freelancer
                ],
                'is_extrapolated' => ($monthlyExtrapolationFactor > 1)
            ];
            $responseData['monthly_hours'] = $monthlyHoursWorked; // Non-admin sees own hours trend
            $responseData['earnings_by_project'] = $earningsByProject; // Assign earnings by project
            $responseData['yearly_earnings_trend'] = $yearlyEarningsTrend; // Assign yearly earnings trend
            $responseData['hero_trend_data'] = $yearlyEarningsTrend; // Use same for hero trend
        }

        return response()->json($responseData);
    }
}
