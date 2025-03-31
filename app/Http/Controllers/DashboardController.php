<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WorkLog;
use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = Carbon::now();
        $thisYear = $now->copy()->startOfYear();
        $lastYear = $now->copy()->subYear()->startOfYear();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->startOfMonth()->subMonth(); // do not change order of startOfMonth and subMonth. It will break the logic.

        // Calculate extrapolation factor for current month
        $daysInMonth = $now->daysInMonth;
        $currentDay = $now->day;
        $extrapolationFactor = $daysInMonth / $currentDay;

        // Revenue KPIs - Only using invoice amounts
        $yearlyRevenue = Invoice::where('status', 'paid')
            ->whereYear('issue_date', $now->year)
            ->sum('total_amount');

        $lastYearRevenue = Invoice::where('status', 'paid')
            ->whereYear('issue_date', $lastYear->year)
            ->sum('total_amount');

        // Current month revenue (with extrapolation)
        $currentMonthRevenue = Invoice::where('status', 'paid')
            ->whereMonth('issue_date', $now->month)
            ->whereYear('issue_date', $now->year)
            ->sum('total_amount');
        $monthlyRevenue = number_format($currentMonthRevenue * $extrapolationFactor, 2, '.', '');

        // Last month revenue (actual)
        $lastMonthRevenue = Invoice::where('status', 'paid')
            ->whereMonth('issue_date', $lastMonth->month)
            ->whereYear('issue_date', $lastMonth->year)
            ->sum('total_amount');

        // Hours KPIs
        $yearlyHours = WorkLog::whereYear('date', $now->year)
            ->sum('hours_worked');

        $lastYearHours = WorkLog::whereYear('date', $lastYear->year)
            ->sum('hours_worked');

        // Current month hours (with extrapolation)
        $currentMonthHours = WorkLog::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('hours_worked');
        $monthlyHours = number_format($currentMonthHours * $extrapolationFactor, 2, '.', '');

        // Last month hours (actual)
        $lastMonthHours = WorkLog::whereMonth('date', $lastMonth->month)
            ->whereYear('date', $lastMonth->year)
            ->sum('hours_worked');

        // Billable Hours
        $yearlyBillableHours = WorkLog::where('billable', true)
            ->whereYear('date', $now->year)
            ->sum('hours_worked');

        $lastYearBillableHours = WorkLog::where('billable', true)
            ->whereYear('date', $lastYear->year)
            ->sum('hours_worked');

        // Current month billable hours (with extrapolation)
        $currentMonthBillableHours = WorkLog::where('billable', true)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('hours_worked');
        $monthlyBillableHours = number_format($currentMonthBillableHours * $extrapolationFactor, 2, '.', '');

        // Last month billable hours (actual)
        $lastMonthBillableHours = WorkLog::where('billable', true)
            ->whereMonth('date', $lastMonth->month)
            ->whereYear('date', $lastMonth->year)
            ->sum('hours_worked');

        // Project KPIs
        $activeProjects = Project::whereNull('deadline')
            ->orWhere('deadline', '>', $now)
            ->count();

        $overdueProjects = Project::where('deadline', '<', $now)
            ->whereNotNull('deadline')
            ->count();

        // Revenue by Customer (all time)
        $revenueByCustomer = Invoice::with('customer')
            ->where('status', 'paid')
            ->get()
            ->groupBy('customer.name')
            ->map(function ($invoices) {
                return $invoices->sum('total_amount');
            })
            ->sortDesc();

        // Yearly Revenue Trend
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

        // Monthly Hours Worked (with extrapolation for current month)
        $monthlyHoursWorked = WorkLog::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->selectRaw('date, SUM(hours_worked) as total, SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) use ($extrapolationFactor) {
                return [
                    'date' => $item->date,
                    'hours' => number_format($item->total * $extrapolationFactor, 2, '.', ''),
                    'billable' => number_format($item->billable * $extrapolationFactor, 2, '.', '')
                ];
            });

        // Upcoming Deadlines
        $upcomingDeadlines = Project::with('customer')
            ->whereNotNull('deadline')
            ->where('deadline', '>', $now)
            ->orderBy('deadline')
            ->limit(6)
            ->get()
            ->map(function ($project) use ($now) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'customer' => $project->customer->name,
                    'deadline' => $project->deadline->format('Y-m-d'),
                    'days_until' => $now->diffInDays($project->deadline, false)
                ];
            });

        return response()->json([
            'kpis' => [
                'revenue' => [
                    'yearly' => $yearlyRevenue,
                    'last_year' => $lastYearRevenue,
                    'monthly' => $monthlyRevenue,
                    'last_month' => $lastMonthRevenue,
                    'is_extrapolated' => true
                ],
                'hours' => [
                    'yearly' => [
                        'total' => $yearlyHours,
                        'billable' => $yearlyBillableHours
                    ],
                    'last_year' => [
                        'total' => $lastYearHours,
                        'billable' => $lastYearBillableHours
                    ],
                    'monthly' => [
                        'total' => $monthlyHours,
                        'billable' => $monthlyBillableHours
                    ],
                    'last_month' => [
                        'total' => $lastMonthHours,
                        'billable' => $lastMonthBillableHours
                    ]
                ],
                'projects' => [
                    'active' => $activeProjects,
                    'overdue' => $overdueProjects
                ]
            ],
            'revenue_by_customer' => $revenueByCustomer,
            'yearly_revenue_trend' => $yearlyRevenueTrend,
            'monthly_hours' => $monthlyHoursWorked,
            'upcoming_deadlines' => $upcomingDeadlines
        ]);
    }
}
