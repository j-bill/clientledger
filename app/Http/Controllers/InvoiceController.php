<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WorkLog;
use App\Models\Project;
use App\Models\Setting;
use App\Services\InvoiceNumberGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $taxRate = floatval(Setting::where('key', 'tax_rate')->value('value') ?? 0);
        
        $invoices = Invoice::with(['customer', 'workLogs'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($invoice) use ($taxRate) {
                $invoice->tax_rate = $taxRate;
                $invoice->tax_amount = $invoice->total_amount * ($taxRate / 100);
                $invoice->total_with_tax = $invoice->total_amount + $invoice->tax_amount;
                return $invoice;
            });

        return response()->json($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_number' => 'nullable|string',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'total_amount' => 'required|numeric',
            // align with DB enum
            'status' => 'required|string|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'work_logs' => 'sometimes|array|nullable',
            'work_logs.*' => 'integer|exists:work_logs,id',
        ]);

        // Generate invoice number if not provided
        if (empty($validated['invoice_number'])) {
            $validated['invoice_number'] = InvoiceNumberGenerator::generate();
        }

        // Strip empty notes to avoid issues if column not present yet
        if (array_key_exists('notes', $validated) && ($validated['notes'] === null || $validated['notes'] === '')) {
            unset($validated['notes']);
        }
        $workLogs = $validated['work_logs'] ?? [];
        unset($validated['work_logs']);
        
        $invoice = Invoice::create($validated);
        
        if (!empty($workLogs)) {
            $invoice->workLogs()->attach($workLogs);
        }
        
        // Add tax rate and calculated tax to response
        $taxRate = floatval(Setting::where('key', 'tax_rate')->value('value') ?? 0);
        $invoice->load(['customer', 'workLogs']);
        $invoice->tax_rate = $taxRate;
        $invoice->tax_amount = $invoice->total_amount * ($taxRate / 100);
        $invoice->total_with_tax = $invoice->total_amount + $invoice->tax_amount;
        
        return response()->json($invoice, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $taxRate = floatval(Setting::where('key', 'tax_rate')->value('value') ?? 0);
        $invoice->load(['customer', 'workLogs']);
        $invoice->tax_rate = $taxRate;
        $invoice->tax_amount = $invoice->total_amount * ($taxRate / 100);
        $invoice->total_with_tax = $invoice->total_amount + $invoice->tax_amount;
        
        return response()->json($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'invoice_number' => 'sometimes|nullable|string',
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date',
            'total_amount' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|string|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
        ]);

        // Strip empty notes
        if (array_key_exists('notes', $validated) && ($validated['notes'] === null || $validated['notes'] === '')) {
            unset($validated['notes']);
        }

        $invoice->update($validated);
        $invoice->load(['customer', 'workLogs']);
        
        // Add tax rate and calculated tax to response
        $taxRate = floatval(Setting::where('key', 'tax_rate')->value('value') ?? 0);
        $invoice->tax_rate = $taxRate;
        $invoice->tax_amount = $invoice->total_amount * ($taxRate / 100);
        $invoice->total_with_tax = $invoice->total_amount + $invoice->tax_amount;
        
        return response()->json($invoice);
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
            'due_date' => 'required|date',
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
            // Use project rate if set, otherwise fall back to customer rate
            $rate = $project->hourly_rate ?? $customer->hourly_rate ?? 0;
            $totalAmount += $log->hours_worked * $rate;
        }

        // Generate unique invoice number
        $invoiceNumber = InvoiceNumberGenerator::generate();

        // Create invoice with today's date as issue_date
        $invoice = Invoice::create([
            'customer_id' => $validated['customer_id'],
            'invoice_number' => $invoiceNumber,
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => $validated['due_date'],
            'total_amount' => $totalAmount,
            'status' => $validated['status'],
        ]);

        // Attach work logs to the invoice
        $invoice->workLogs()->attach($validated['work_log_ids']);

        // Add tax rate and calculated tax to response
        $taxRate = floatval(Setting::where('key', 'tax_rate')->value('value') ?? 0);
        $invoice->load(['customer', 'workLogs']);
        $invoice->tax_rate = $taxRate;
        $invoice->tax_amount = $invoice->total_amount * ($taxRate / 100);
        $invoice->total_with_tax = $invoice->total_amount + $invoice->tax_amount;

        return response()->json($invoice, 201);
    }

    /**
     * Get billable, unbilled work logs for a customer (to select for invoice generation)
     */
    public function unbilledWorkLogs(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $query = WorkLog::whereHas('project', function ($q) use ($validated) {
                $q->where('customer_id', $validated['customer_id']);
            })
            ->where('billable', true)
            ->whereDoesntHave('invoices');

        // Apply project filter if specified
        if (!empty($validated['project_id'])) {
            $query->where('project_id', $validated['project_id']);
        }

        // Apply date range filters if specified
        if (!empty($validated['start_date'])) {
            $query->where('date', '>=', $validated['start_date']);
        }

        if (!empty($validated['end_date'])) {
            $query->where('date', '<=', $validated['end_date']);
        }

        $logs = $query->with(['project', 'user'])
            ->orderByDesc('date')
            ->get();

        // Append billing rate to each log for the invoice preview
        $logs = $logs->map(function ($log) {
            $log->billing_rate = $log->getBillingRate();
            $log->billing_amount = $log->calculateBillingAmount();
            return $log;
        });

        return response()->json($logs);
    }

    /**
     * Get projects for a customer (for filtering work logs)
     */
    public function customerProjects(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $projects = Project::where('customer_id', $validated['customer_id'])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($projects);
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(Invoice $invoice)
    {
        $pdfGenerator = new \App\Services\InvoicePdfGenerator();
        return $pdfGenerator->download($invoice);
    }

    /**
     * Stream invoice PDF for viewing
     */
    public function viewPdf(Invoice $invoice)
    {
        $pdfGenerator = new \App\Services\InvoicePdfGenerator();
        return $pdfGenerator->stream($invoice);
    }
}
