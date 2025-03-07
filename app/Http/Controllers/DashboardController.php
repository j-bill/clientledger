<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        // Get total unbilled hours
        $unbilledHours = WorkLog::where('billable', true)
            ->whereDoesntHave('invoices')
            ->sum('hours_worked');
            
        // Total revenue this month
        $revenueThisMonth = Invoice::where('status', 'paid')
            ->whereMonth('issue_date', Carbon::now()->month)
            ->whereYear('issue_date', Carbon::now()->year)
            ->sum('total_amount');
            
        // Upcoming deadlines
        $upcomingDeadlines = Project::where('deadline', '>=', Carbon::now())
            ->where('deadline', '<=', Carbon::now()->addDays(14))
            ->with('customer')
            ->get();
            
        // Outstanding invoices
        $outstandingInvoices = Invoice::where('status', 'sent')
            ->where('due_date', '<', Carbon::now())
            ->with('customer')
            ->get();
            
        // Recent activity
        $recentWorkLogs = WorkLog::with(['project', 'project.customer'])
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();
            
        return response()->json([
            'unbilled_hours' => $unbilledHours,
            'revenue_this_month' => $revenueThisMonth,
            'upcoming_deadlines' => $upcomingDeadlines,
            'outstanding_invoices' => $outstandingInvoices,
            'recent_activity' => $recentWorkLogs,
        ]);
    }
}
