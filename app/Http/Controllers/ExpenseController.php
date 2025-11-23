<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['customer', 'project']);

        if ($request->has('start_date') && $request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        return $query->orderBy('date', 'desc')->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'date' => 'required|date',
            'category' => 'required|string|max:50',
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'receipt' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
            'is_tax_deductible' => 'boolean',
        ]);

        $path = null;
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts', 'public');
        }

        $expense = Expense::create([
            ...$validated,
            'receipt_path' => $path,
        ]);

        return response()->json($expense, 201);
    }

    public function show(Request $request, Expense $expense)
    {
        return $expense->load(['customer', 'project']);
    }

    public function update(Request $request, Expense $expense)
    {

        $validated = $request->validate([
            'description' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'date' => 'sometimes|required|date',
            'category' => 'sometimes|required|string|max:50',
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'receipt' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
            'is_tax_deductible' => 'boolean',
        ]);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $validated['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        $expense->update($validated);

        return response()->json($expense);
    }

    public function destroy(Request $request, Expense $expense)
    {
        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return response()->noContent();
    }

    public function export(Request $request)
    {
        $query = Expense::with(['customer', 'project']);

        if ($request->has('start_date') && $request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $expenses = $query->orderBy('date', 'desc')->get();

        $csvFileName = 'expenses_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($expenses) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Description', 'Amount', 'Currency', 'Category', 'Project', 'Customer', 'Tax Deductible']);

            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->date->format('Y-m-d'),
                    $expense->description,
                    $expense->amount,
                    $expense->currency,
                    $expense->category,
                    $expense->project ? $expense->project->name : '',
                    $expense->customer ? $expense->customer->name : '',
                    $expense->is_tax_deductible ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
