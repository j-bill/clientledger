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

        // Extrapolation factor
        $daysInMonth = $now->daysInMonth;
        $currentDay = $now->day;
        $extrapolationFactor = ($currentDay > 0 && $currentDay < $daysInMonth) ? $daysInMonth / $currentDay : 1; // Avoid extrapolation on day 1 or last day

        // Initialize response data structure with defaults for all keys
        $responseData = [
            'kpis' => [
                'revenue' => [
                    'yearly' => 0,
                    'last_year' => 0,
                    'monthly' => 0,
                    'last_month' => 0,
                    'is_extrapolated' => false
                ],
                'hours' => [
                    'yearly' => ['total' => 0, 'billable' => 0],
                    'last_year' => ['total' => 0, 'billable' => 0],
                    'monthly' => ['total' => 0, 'billable' => 0],
                    'last_month' => ['total' => 0, 'billable' => 0]
                ],
                'projects' => [
                    'active' => 0,
                    'overdue' => 0
                ],
                'earnings' => [ // Add default earnings structure
                    'yearly' => 0,
                    'last_year' => 0,
                    'monthly' => 0,
                    'last_month' => 0,
                    'is_extrapolated' => false
                ]
            ],
            'revenue_by_customer' => [],
            'yearly_revenue_trend' => [],
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
        $lastYearHours = (clone $workLogQueryBase)
            ->whereBetween('date', [$lastYearStart, $lastYearEnd])
            ->sum('hours_worked');
        $currentMonthHours = (clone $workLogQueryBase)
            ->whereBetween('date', [$thisMonthStart, $now]) // Sum up to current day for extrapolation base
            ->sum('hours_worked');
        $monthlyHours = number_format($currentMonthHours * $extrapolationFactor, 2, '.', '');
        $lastMonthHours = (clone $workLogQueryBase)
            ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
            ->sum('hours_worked');

        // --- Billable Hours KPIs (Common for both roles, but filtered) ---
        $billableWorkLogQuery = (clone $workLogQueryBase)->where('billable', true);
        $yearlyBillableHours = (clone $billableWorkLogQuery)
            ->whereYear('date', $now->year)
            ->sum('hours_worked');
        $lastYearBillableHours = (clone $billableWorkLogQuery)
            ->whereBetween('date', [$lastYearStart, $lastYearEnd])
            ->sum('hours_worked');
        $currentMonthBillableHours = (clone $billableWorkLogQuery)
            ->whereBetween('date', [$thisMonthStart, $now])
            ->sum('hours_worked');
        $monthlyBillableHours = number_format($currentMonthBillableHours * $extrapolationFactor, 2, '.', '');
        $lastMonthBillableHours = (clone $billableWorkLogQuery)
            ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
            ->sum('hours_worked');

        // Assign common hours KPIs
        $responseData['kpis']['hours'] = [
            'yearly' => [
                'total' => $yearlyHours,
                'billable' => $yearlyBillableHours
            ],
            'last_year' => [
                'total' => $lastYearHours,
                'billable' => $lastYearBillableHours
            ],
            'monthly' => [
                'total' => $monthlyHours, // Keep extrapolated total for KPI card
                'billable' => $monthlyBillableHours, // Keep extrapolated billable for KPI card
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
            $invoiceQueryBase = Invoice::where('status', 'paid');
            $yearlyRevenue = (clone $invoiceQueryBase)
                ->whereYear('issue_date', $now->year)
                ->sum('total_amount');
            $lastYearRevenue = (clone $invoiceQueryBase)
                ->whereBetween('issue_date', [$lastYearStart, $lastYearEnd])
                ->sum('total_amount');
            
            // For current month, we need to calculate from work logs since invoices haven't been created yet
            $currentMonthRevenue = WorkLog::where('billable', true)
                ->whereBetween('date', [$thisMonthStart, $now])
                ->sum(DB::raw('hours_worked * hourly_rate')); // Use client hourly rate
            $monthlyRevenue = number_format($currentMonthRevenue * $extrapolationFactor, 2, '.', '');
            
            $lastMonthRevenue = (clone $invoiceQueryBase)
                ->whereBetween('issue_date', [$lastMonthStart, $lastMonthEnd])
                ->sum('total_amount');

            // --- Admin: Revenue by Customer ---
            $revenueByCustomer = Invoice::with('customer')
                ->where('status', 'paid')
                ->get()
                ->groupBy('customer.name')
                ->map(function ($invoices) {
                    return $invoices->sum('total_amount');
                })
                ->sortDesc();

            // --- Admin: Yearly Revenue Trend ---
            $yearlyRevenueTrend = Invoice::where('status', 'paid')
                ->whereYear('issue_date', $now->year)
                ->selectRaw('DATE(issue_date) as date, SUM(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'amount' => $item->total
                    ];
                });

            // --- Admin: Monthly Hours Worked Trend (All Users) ---
            $monthlyHoursData = WorkLog::whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->selectRaw('DATE(date) as date, SUM(hours_worked) as total, SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable')
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
                'yearly' => $yearlyRevenue,
                'last_year' => $lastYearRevenue,
                'monthly' => floatval($monthlyRevenue), // Convert from string to float
                'last_month' => $lastMonthRevenue,
                'is_extrapolated' => ($extrapolationFactor > 1)
            ];
            $responseData['monthly_hours'] = $monthlyHoursWorked; // Admin sees all hours trend
            $responseData['revenue_by_customer'] = $revenueByCustomer; // Assign revenue by customer
            $responseData['yearly_revenue_trend'] = $yearlyRevenueTrend; // Assign yearly revenue trend

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
            $monthlyEarnings = number_format($currentMonthEarnings * $extrapolationFactor, 2, '.', '');
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
                ->selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(hours_worked * user_hourly_rate) as total')
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
                ->selectRaw('DATE(date) as date, SUM(hours_worked) as total, SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable')
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
                'yearly' => $yearlyEarnings,
                'last_year' => $lastYearEarnings,
                'monthly' => floatval($monthlyEarnings), // Convert from string to float
                'last_month' => $lastMonthEarnings,
                'is_extrapolated' => ($extrapolationFactor > 1)
            ];
            $responseData['monthly_hours'] = $monthlyHoursWorked; // Non-admin sees own hours trend
            $responseData['earnings_by_project'] = $earningsByProject; // Assign earnings by project
            $responseData['yearly_earnings_trend'] = $yearlyEarningsTrend; // Assign yearly earnings trend
        }

        return response()->json($responseData);
    }
}
