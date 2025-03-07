<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use Illuminate\Http\Request;

class WorkLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WorkLog::with('project');

        // Date range filter
        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Project filter
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Billable filter
        if ($request->has('billable')) {
            $query->where('billable', $request->boolean('billable'));
        }

        // Sort options
        $sortField = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
            'billable' => 'boolean',
        ]);

        $hours_worked = (strtotime($validated['end_time']) - strtotime($validated['start_time'])) / 3600;
        $validated['hours_worked'] = $hours_worked;

        $workLog = WorkLog::create($validated);
        return response()->json($workLog->load('project'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkLog $workLog)
    {
        return response()->json($workLog->load('project'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkLog $workLog)
    {
        $validated = $request->validate([
            'project_id' => 'sometimes|required|exists:projects,id',
            'date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
            'billable' => 'boolean',
        ]);

        $hours_worked = (strtotime($validated['end_time']) - strtotime($validated['start_time'])) / 3600;
        $validated['hours_worked'] = $hours_worked;

        $workLog->update($validated);
        return response()->json($workLog->load('project'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkLog $workLog)
    {
        $workLog->delete();
        return response()->json(null, 204);
    }
}
