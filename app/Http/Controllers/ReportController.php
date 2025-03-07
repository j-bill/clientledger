<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Generate a time report by project and date range
     */
    public function timeByProject(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'project_id' => 'sometimes|exists:projects,id',
            'customer_id' => 'sometimes|exists:customers,id',
        ]);
        
        $query = WorkLog::select(
            'project_id',
            DB::raw('SUM(hours_worked) as total_hours'),
            DB::raw('SUM(CASE WHEN billable = 1 THEN hours_worked ELSE 0 END) as billable_hours'),
            DB::raw('SUM(CASE WHEN billable = 0 THEN hours_worked ELSE 0 END) as non_billable_hours')
        )
        ->whereBetween('date', [$validated['start_date'], $validated['end_date']])
        ->with('project.customer')
        ->groupBy('project_id');
        
        if (isset($validated['project_id'])) {
            $query->where('project_id', $validated['project_id']);
        }
        
        if (isset($validated['customer_id'])) {
            $query->whereHas('project', function ($q) use ($validated) {
                $q->where('customer_id', $validated['customer_id']);
            });
        }
        
        $results = $query->get();
        
        return response()->json([
            'period' => [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
            'data' => $results,
            'totals' => [
                'total_hours' => $results->sum('total_hours'),
                'billable_hours' => $results->sum('billable_hours'),
                'non_billable_hours' => $results->sum('non_billable_hours'),
            ]
        ]);
    }
    
    /**
     * Generate a financial report by customer
     */
    public function financialByCustomer(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $invoices = Invoice::select(
            'customer_id',
            DB::raw('SUM(total_amount) as total_invoiced'),
            DB::raw('SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as total_paid'),
            DB::raw('SUM(CASE WHEN status = "sent" THEN total_amount ELSE 0 END) as total_pending')
        )
        ->whereBetween('issue_date', [$validated['start_date'], $validated['end_date']])
        ->with('customer')
        ->groupBy('customer_id')
        ->get();
        
        return response()->json([
            'period' => [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
            'data' => $invoices,
            'totals' => [
                'total_invoiced' => $invoices->sum('total_invoiced'),
                'total_paid' => $invoices->sum('total_paid'),
                'total_pending' => $invoices->sum('total_pending'),
            ]
        ]);
    }
}
