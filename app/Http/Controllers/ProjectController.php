<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Project::with(['customer', 'users']);

        // Filter by customers
        if ($request->has('customers') && is_array($request->customers) && !empty($request->customers)) {
            $query->whereIn('customer_id', $request->customers);
        }

        // Filter by freelancers
        if ($request->has('freelancers') && is_array($request->freelancers) && !empty($request->freelancers)) {
            $query->whereHas('users', function ($q) use ($request) {
                $q->whereIn('users.id', $request->freelancers);
            });
        }

        return response()->json($query->get());
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
            'users' => 'required|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.hourly_rate' => 'required|numeric|min:0',
        ]);

        if (isset($validated['deadline'])) {
            $validated['deadline'] = \Carbon\Carbon::parse($validated['deadline']);
        }

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'customer_id' => $validated['customer_id'],
            'hourly_rate' => $validated['hourly_rate'],
            'deadline' => $validated['deadline'],
        ]);

        // Attach users with their hourly rates
        foreach ($validated['users'] as $userData) {
            $project->users()->attach($userData['id'], [
                'hourly_rate' => $userData['hourly_rate']
            ]);
        }

        return response()->json($project->load('customer', 'users'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$project->users->contains($user->id)) {
            abort(403, 'Unauthorized access to this project.');
        }

        return response()->json($project->load('customer', 'users'));
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
            'users' => 'sometimes|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.hourly_rate' => 'required|numeric|min:0',
        ]);

        if (isset($validated['deadline'])) {
            $validated['deadline'] = \Carbon\Carbon::parse($validated['deadline']);
        }

        $project->update([
            'name' => $validated['name'] ?? $project->name,
            'description' => $validated['description'] ?? $project->description,
            'customer_id' => $validated['customer_id'] ?? $project->customer_id,
            'hourly_rate' => $validated['hourly_rate'] ?? $project->hourly_rate,
            'deadline' => $validated['deadline'] ?? $project->deadline,
        ]);

        // Update users if provided
        if (isset($validated['users'])) {
            $project->users()->detach();
            foreach ($validated['users'] as $userData) {
                $project->users()->attach($userData['id'], [
                    'hourly_rate' => $userData['hourly_rate']
                ]);
            }
        }

        return response()->json($project->load('customer', 'users'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Project $project)
    {
        $project->delete();
        return response()->json(null, 204);
    }
}
