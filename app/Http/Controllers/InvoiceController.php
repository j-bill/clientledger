<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\WorkLog;
use App\Services\InvoiceNumberGenerator;
use App\Services\InvoicePdfGenerator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $invoices = Invoice::with(['customer', 'workLogs', 'items'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request, [
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
            'items' => 'sometimes|array|nullable',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric',
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
        $items = $this->itemRows($validated['items'] ?? null);
        unset($validated['work_logs'], $validated['items']);

        $invoice = Invoice::create($validated);

        if (! empty($workLogs)) {
            $invoice->workLogs()->attach($workLogs);
        }

        $this->syncItems($invoice, $items);

        $invoice->load(['customer', 'workLogs', 'items']);

        return response()->json($invoice, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['customer', 'workLogs', 'items']);

        return response()->json($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $this->validated($request, [
            'customer_id' => 'sometimes|required|exists:customers,id',
            'invoice_number' => 'sometimes|nullable|string',
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date',
            'total_amount' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|string|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|nullable',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric',
        ]);

        // Strip empty notes
        if (array_key_exists('notes', $validated) && ($validated['notes'] === null || $validated['notes'] === '')) {
            unset($validated['notes']);
        }

        // An absent key leaves items untouched; a present key (even an
        // empty array) replaces the full set.
        $items = null;
        if (array_key_exists('items', $validated)) {
            $items = $this->itemRows($validated['items']);
            unset($validated['items']);
        }

        $invoice->update($validated);

        if ($items !== null) {
            $invoice->items()->delete();
            $this->syncItems($invoice, $items);
        }

        $invoice->load(['customer', 'workLogs', 'items']);

        return response()->json($invoice);
    }

    /**
     * Normalize the validated `items` payload into typed rows. Validation
     * already guarantees the shape; this narrows the types for static
     * analysis and coerces the numerics.
     *
     * @return list<array{description: string, quantity: float, unit_price: float}>
     */
    private function itemRows(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $description = $item['description'] ?? null;
            $quantity = $item['quantity'] ?? null;
            $unitPrice = $item['unit_price'] ?? null;

            if (! is_string($description) || ! is_numeric($quantity) || ! is_numeric($unitPrice)) {
                continue;
            }

            $rows[] = [
                'description' => $description,
                'quantity' => (float) $quantity,
                'unit_price' => (float) $unitPrice,
            ];
        }

        return $rows;
    }

    /**
     * Persist manual line items for an invoice, preserving submitted order.
     *
     * @param  list<array{description: string, quantity: float, unit_price: float}>  $items
     */
    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $index => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        // Delete PDF file if it exists
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            Storage::delete($invoice->pdf_path);
        }

        $invoice->workLogs()->detach();
        $invoice->delete();

        return response()->json(null, 204);
    }

    /**
     * Upload an existing invoice PDF.
     * This is for migrating from old systems.
     * If an existing PDF exists, it will be replaced.
     */
    public function uploadPdf(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        // Delete old PDF if it exists
        if ($invoice->pdf_path) {
            Storage::delete($invoice->pdf_path);
        }

        // Store the uploaded PDF
        $file = $request->file('pdf');
        $filename = "invoice-{$invoice->invoice_number}.pdf";
        $path = $file->storeAs('invoices', $filename);

        // Update invoice with the PDF path
        $invoice->update(['pdf_path' => $path]);

        return response()->json([
            'message' => 'Invoice PDF uploaded successfully',
            'pdf_path' => $path,
        ]);
    }

    /**
     * Generate a PDF for an invoice.
     * If an existing PDF exists, it will be replaced.
     */
    public function generatePdf(Invoice $invoice): JsonResponse
    {
        // Delete old PDF if it exists
        if ($invoice->pdf_path) {
            Storage::delete($invoice->pdf_path);
        }

        // Generate and save PDF to filesystem
        $pdfGenerator = new InvoicePdfGenerator;
        $pdfPath = $pdfGenerator->saveToStorage($invoice);
        $invoice->update(['pdf_path' => $pdfPath]);

        return response()->json([
            'message' => 'Invoice PDF generated successfully',
            'pdf_path' => $pdfPath,
        ]);
    }

    /**
     * Generate an invoice from unbilled work logs.
     */
    public function generateFromWorkLogs(Request $request): JsonResponse
    {
        $validated = $this->validated($request, [
            'customer_id' => 'required|exists:customers,id',
            'work_log_ids' => 'required|array',
            'work_log_ids.*' => 'exists:work_logs,id',
            'due_date' => 'required|date',
            'status' => 'required|string|in:draft,sent,paid,overdue,cancelled',
            'items' => 'sometimes|array|nullable',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric',
        ]);

        // Get customer and work logs
        $customer = Customer::findOrFail($request->integer('customer_id'));
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
            $totalAmount += (float) ($log->hours_worked ?? 0) * (float) $rate;
        }

        $items = $this->itemRows($validated['items'] ?? null);
        foreach ($items as $item) {
            $totalAmount += $item['quantity'] * $item['unit_price'];
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

        $this->syncItems($invoice, $items);

        $invoice->load(['customer', 'workLogs', 'items']);

        return response()->json($invoice, 201);
    }

    /**
     * Get billable, unbilled work logs for a customer (to select for invoice generation)
     */
    public function unbilledWorkLogs(Request $request): JsonResponse
    {
        $validated = $this->validated($request, [
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
        if (! empty($validated['project_id'])) {
            $query->where('project_id', $validated['project_id']);
        }

        // Apply date range filters if specified
        if (! empty($validated['start_date'])) {
            $query->where('date', '>=', $validated['start_date']);
        }

        if (! empty($validated['end_date'])) {
            $query->where('date', '<=', $validated['end_date']);
        }

        $logs = $query->with(['project', 'user'])
            ->orderByDesc('date')
            ->get();

        // Expose the billing rate on each log for the invoice preview
        $logs->each->append(['billing_rate', 'billing_amount']);

        return response()->json($logs);
    }

    /**
     * Get projects for a customer (for filtering work logs)
     */
    public function customerProjects(Request $request): JsonResponse
    {
        $validated = $this->validated($request, [
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
    public function downloadPdf(Invoice $invoice): SymfonyResponse
    {
        // If PDF already exists, serve it from storage
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return Storage::download($invoice->pdf_path, "invoice-{$invoice->invoice_number}.pdf");
        }

        // Otherwise generate it on-the-fly (shouldn't happen for new invoices)
        $pdfGenerator = new InvoicePdfGenerator;

        return $pdfGenerator->download($invoice);
    }

    /**
     * Stream invoice PDF for viewing
     */
    public function viewPdf(Invoice $invoice): SymfonyResponse
    {
        // If PDF already exists, serve it from storage
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return response()->file(Storage::path($invoice->pdf_path), [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Otherwise generate it on-the-fly (shouldn't happen for new invoices)
        $pdfGenerator = new InvoicePdfGenerator;

        return $pdfGenerator->stream($invoice);
    }
}
