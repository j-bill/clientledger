<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use App\Models\Project;
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
        $workLogs = $query->paginate($perPage);

        return response()->json([
            'data' => $workLogs->items(),
            'total' => $workLogs->total()
        ]);
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
            'end_time' => 'nullable|date_format:H:i', // Changed from required to nullable
            'description' => 'nullable|string',
            'billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric',
        ]);

        // Calculate hours_worked if both start_time and end_time are provided
        if (isset($validated['start_time']) && isset($validated['end_time'])) {
            $hours_worked = (strtotime($validated['end_time']) - strtotime($validated['start_time'])) / 3600;
            $validated['hours_worked'] = $hours_worked;
        } else {
            // Set hours_worked to null if end_time is not provided
            $validated['hours_worked'] = null;
        }

        // If hourly_rate is not provided, get it from the project
        if (!isset($validated['hourly_rate']) || $validated['hourly_rate'] === null) {
            $project = Project::find($validated['project_id']);
            if ($project && $project->hourly_rate) {
                $validated['hourly_rate'] = $project->hourly_rate;
            }
        }

        $workLog = WorkLog::create($validated);
        return response()->json($workLog->load('project'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($workLog)
    {
        $workLog = WorkLog::where('id', $workLog)->with('project', 'project.customer')->first();
        return response()->json($workLog);
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
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time', // Modified validation rule
            'description' => 'nullable|string',
            'billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric',
        ]);

        // Calculate hours_worked if end_time is provided
        if (isset($validated['end_time'])) {
            // Use the updated start_time if provided, otherwise use the existing one
            $start_time = isset($validated['start_time']) ? $validated['start_time'] : $workLog->start_time;
            $hours_worked = (strtotime($validated['end_time']) - strtotime($start_time)) / 3600;
            $validated['hours_worked'] = $hours_worked;
        }

        // If project_id is changed but hourly_rate is not provided, get it from the new project
        if (isset($validated['project_id']) && $validated['project_id'] !== $workLog->project_id && !isset($validated['hourly_rate'])) {
            $project = Project::find($validated['project_id']);
            if ($project && $project->hourly_rate) {
                $validated['hourly_rate'] = $project->hourly_rate;
            }
        }

        $workLog->update($validated);
        return response()->json($workLog->load('project'));
    }

    /**
     * Complete a work log by setting the end time
     */
    public function completeTracking(Request $request, WorkLog $workLog)
    {
        $validated = $request->validate([
            'end_time' => 'required|date_format:H:i|after_or_equal:start_time',
            'description' => 'nullable|string',
        ]);

        $hours_worked = (strtotime($validated['end_time']) - strtotime($workLog->start_time)) / 3600;
        $validated['hours_worked'] = $hours_worked;

        // Update the description if provided
        if (isset($validated['description']) && !empty($validated['description'])) {
            $workLog->description = $validated['description'];
        }

        $workLog->end_time = $validated['end_time'];
        $workLog->hours_worked = $validated['hours_worked'];
        $workLog->save();

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
