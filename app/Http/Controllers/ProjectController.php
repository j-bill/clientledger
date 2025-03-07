<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Project::with('customer')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'customer_id' => 'required|exists:customers,id',
            'hourly_rate' => 'nullable|numeric',
            'deadline' => 'nullable|date',
        ]);

        // use carbon to format the deadline correctly.
        if (isset($validated['deadline'])) {
            $validated['deadline'] = \Carbon\Carbon::parse($validated['deadline']);
        }

        $project = Project::create($validated);
        return response()->json($project->load('customer'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return response()->json($project->load('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'hourly_rate' => 'nullable|numeric',
            'deadline' => 'nullable|date',
        ]);

        $project->update($validated);
        return response()->json($project->load('customer'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(null, 204);
    }
}
