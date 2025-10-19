<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssigned;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Prepare the query with appropriate relations
        if ($user && $user->isFreelancer()) {
            // For freelancers, only include projects they're assigned to and limit hourly rate data
            $query = Project::with(['customer', 'users' => function($query) use ($user) {
                $query->select('users.id', 'users.name', 'users.email', 'users.role');
            }]);
            
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } else {
            // For admins, include all data
            $query = Project::with(['customer', 'users']);
        }

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

        // Hide the pivot data for users
        $projects = $query->get()->each(function ($project) {
            $project->users->each(function ($user) {
                // Hide the pivot data for each user
                $user->makeHidden(['pivot']);
            });
        });

        return response()->json($projects);
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

        // Attach users with their hourly rates and send notifications
        foreach ($validated['users'] as $userData) {
            $project->users()->attach($userData['id'], [
                'hourly_rate' => $userData['hourly_rate']
            ]);
            
            // Send notification to the assigned user
            $user = User::find($userData['id']);
            if ($user) {
                $user->notify(new ProjectAssigned($project->load('customer'), $userData['hourly_rate']));
            }
        }

        return response()->json($project->load('customer', 'users'), 201);
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
            // Get current user IDs before detaching
            $previousUserIds = $project->users()->pluck('users.id')->toArray();
            
            $project->users()->detach();
            foreach ($validated['users'] as $userData) {
                $project->users()->attach($userData['id'], [
                    'hourly_rate' => $userData['hourly_rate']
                ]);
                
                // Only send notification to newly assigned users (not previously assigned)
                if (!in_array($userData['id'], $previousUserIds)) {
                    $user = User::find($userData['id']);
                    if ($user) {
                        $user->notify(new ProjectAssigned($project->load('customer'), $userData['hourly_rate']));
                    }
                }
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
