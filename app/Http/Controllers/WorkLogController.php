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
        $query = WorkLog::with(['project', 'user'])
            ->forUser($request->user());

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

        // User filter
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Billable filter
        if ($request->has('billable')) {
            $query->where('billable', $request->boolean('billable'));
        }

        // Sort options
        $sortField = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_dir', 'desc');
        
        // Handle special sorting cases
        switch ($sortField) {
            case 'project':
            case 'project.name':
                $query->join('projects', 'work_logs.project_id', '=', 'projects.id')
                    ->orderBy('projects.name', $sortDirection)
                    ->select('work_logs.*');
                break;
            case 'user':
            case 'freelancer':
            case 'user.name':
                $query->join('users', 'work_logs.user_id', '=', 'users.id')
                    ->orderBy('users.name', $sortDirection)
                    ->select('work_logs.*');
                break;
            case 'hours':
                $query->orderBy('hours_worked', $sortDirection);
                break;
            case 'hourly_rate':
            case 'rate':
                $query->orderBy('user_hourly_rate', $sortDirection);
                break;
            case 'amount':
                // Sort by calculated amount (hours * rate)
                $query->orderByRaw("(hours_worked * user_hourly_rate) {$sortDirection}");
                break;
            default:
                $query->orderBy($sortField, $sortDirection);
                break;
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $workLogs = $query->paginate($perPage);

        // Transform the data to include calculated fields
        $transformedData = $workLogs->items();
        foreach ($transformedData as &$workLog) {
            // Calculate hours if not set
            if (!$workLog->hours_worked && $workLog->start_time && $workLog->end_time) {
                $start = strtotime($workLog->start_time);
                $end = strtotime($workLog->end_time);
                $hours = ($end - $start) / 3600;
                $workLog->hours_worked = $hours;
            }
            
            // Calculate amount using user's hourly rate
            if ($workLog->hours_worked && $workLog->user_hourly_rate) {
                $workLog->amount = $workLog->hours_worked * $workLog->user_hourly_rate;
            }
        }

        return response()->json([
            'data' => $transformedData,
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
            'end_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string',
            'billable' => 'boolean',
        ]);

        // Get the user
        $user = $request->user();

        // If end_time is not provided, check for existing active work logs
        if (!isset($validated['end_time']) || $validated['end_time'] === null) {
            $activeWorkLog = WorkLog::where('user_id', $user->id)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->first();
                
            if ($activeWorkLog) {
                return response()->json([
                    'message' => 'You already have an active work log. Please complete it before starting a new one.',
                    'active_work_log' => $activeWorkLog->load('project')
                ], 422);
            }
        }

        // Calculate hours_worked if both start_time and end_time are provided
        if (isset($validated['start_time']) && isset($validated['end_time'])) {
            $hours_worked = (strtotime($validated['end_time']) - strtotime($validated['start_time'])) / 3600;
            $validated['hours_worked'] = $hours_worked;
        } else {
            $validated['hours_worked'] = null;
        }

        // Get the project and user
        $project = Project::find($validated['project_id']);

        // Ensure user is assigned to the project
        if (!$project->users->contains($user->id)) {
            abort(403, 'You are not assigned to this project.');
        }

        // Set the project's hourly rate (for invoice generation)
        $validated['hourly_rate'] = $project->hourly_rate;

        // Set the user's hourly rate for this project
        $validated['user_hourly_rate'] = $user->getProjectHourlyRate($project);
        $validated['user_id'] = $user->id;

        $workLog = WorkLog::create($validated);
        return response()->json($workLog->load(['project', 'user']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, WorkLog $workLog)
    {
        // Check if user has access to this work log
        if (!$request->user()->isAdmin() && $workLog->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this work log.');
        }

        return response()->json($workLog->load('project', 'project.customer', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkLog $workLog)
    {
        // Check if user has access to this work log
        if (!$request->user()->isAdmin() && $workLog->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this work log.');
        }

        $validated = $request->validate([
            'project_id' => 'sometimes|required|exists:projects,id',
            'date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'description' => 'nullable|string',
            'billable' => 'boolean',
        ]);

        // Calculate hours_worked if end_time is provided
        if (isset($validated['end_time'])) {
            $start_time = isset($validated['start_time']) ? $validated['start_time'] : $workLog->start_time;
            $hours_worked = (strtotime($validated['end_time']) - strtotime($start_time)) / 3600;
            $validated['hours_worked'] = $hours_worked;
        }

        // Update hourly rates if project changes
        if (isset($validated['project_id']) && $validated['project_id'] !== $workLog->project_id) {
            $project = Project::find($validated['project_id']);
            
            // Ensure user is assigned to the new project
            if (!$project->users->contains($request->user()->id)) {
                abort(403, 'You are not assigned to this project.');
            }

            $validated['hourly_rate'] = $project->hourly_rate;
            $validated['user_hourly_rate'] = $request->user()->getProjectHourlyRate($project);
        }

        $workLog->update($validated);
        return response()->json($workLog->load(['project', 'user']));
    }

    /**
     * Complete a work log by setting the end time
     */
    public function completeTracking(Request $request, WorkLog $workLog)
    {
        // Check if user has access to this work log
        if (!$request->user()->isAdmin() && $workLog->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this work log.');
        }

        $validated = $request->validate([
            'end_time' => 'required|date_format:H:i|after_or_equal:start_time',
            'description' => 'nullable|string',
        ]);

        $hours_worked = (strtotime($validated['end_time']) - strtotime($workLog->start_time)) / 3600;
        $validated['hours_worked'] = $hours_worked;

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
    public function destroy(Request $request, WorkLog $workLog)
    {
        // Check if user has access to this work log
        if (!$request->user()->isAdmin() && $workLog->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this work log.');
        }

        $workLog->delete();
        return response()->json(null, 204);
    }

    /**
     * Get the user's active work log (one with start_time but no end_time)
     */
    public function getActiveWorkLog(Request $request)
    {
        $user = $request->user();
        
        $activeWorkLog = WorkLog::where('user_id', $user->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->with('project')
            ->first();
            
        if ($activeWorkLog) {
            return response()->json($activeWorkLog);
        }
        
        return response()->json(null);
    }
}
