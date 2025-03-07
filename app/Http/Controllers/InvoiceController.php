<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WorkLog;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Invoice::with(['customer', 'workLogs'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'total_amount' => 'required|numeric',
            'status' => 'required|string|in:draft,sent,paid,overdue,cancelled',
            'work_logs' => 'sometimes|array',
            'work_logs.*' => 'exists:work_logs,id',
        ]);

        $workLogs = $validated['work_logs'] ?? [];
        unset($validated['work_logs']);
        
        $invoice = Invoice::create($validated);
        
        if (!empty($workLogs)) {
            $invoice->workLogs()->attach($workLogs);
        }
        
        return response()->json($invoice->load(['customer', 'workLogs']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load(['customer', 'workLogs']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date|after_or_equal:issue_date',
            'total_amount' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|string|in:draft,sent,paid,overdue,cancelled',
            'work_logs' => 'sometimes|array',
            'work_logs.*' => 'exists:work_logs,id',
        ]);

        if (isset($validated['work_logs'])) {
            $workLogs = $validated['work_logs'];
            unset($validated['work_logs']);
            $invoice->workLogs()->sync($workLogs);
        }

        $invoice->update($validated);
        return response()->json($invoice->load(['customer', 'workLogs']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->workLogs()->detach();
        $invoice->delete();
        return response()->json(null, 204);
    }

    /**
     * Generate an invoice from unbilled work logs.
     */
    public function generateFromWorkLogs(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'work_log_ids' => 'required|array',
            'work_log_ids.*' => 'exists:work_logs,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'status' => 'required|string|in:draft,sent,paid,overdue,cancelled',
        ]);

        // Get customer and work logs
        $customer = Customer::findOrFail($validated['customer_id']);
        $workLogs = WorkLog::whereIn('id', $validated['work_log_ids'])
            ->where('billable', true)
            ->get();
            
        if ($workLogs->isEmpty()) {
            return response()->json(['message' => 'No billable work logs found'], 400);
        }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($workLogs as $log) {
            $project = Project::findOrFail($log->project_id);
            $rate = $project->hourly_rate ?? $customer->hourly_rate ?? 0;
            $totalAmount += $log->hours_worked * $rate;
        }

        // Create invoice
        $invoice = Invoice::create([
            'customer_id' => $validated['customer_id'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'total_amount' => $totalAmount,
            'status' => $validated['status'],
        ]);

        // Attach work logs to the invoice
        $invoice->workLogs()->attach($validated['work_log_ids']);

        return response()->json($invoice->load(['customer', 'workLogs']), 201);
    }
}
