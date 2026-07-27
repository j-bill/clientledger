<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssigned;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Prepare the query with appropriate relations
        if ($user && $user->isFreelancer()) {
            // For freelancers, only include projects they're assigned to and limit hourly rate data
            $query = Project::with(['customer', 'users' => function (BelongsToMany $query) {
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
        if ($request->has('customers') && is_array($request->customers) && ! empty($request->customers)) {
            $query->whereIn('customer_id', $request->customers);
        }

        // Filter by freelancers
        if ($request->has('freelancers') && is_array($request->freelancers) && ! empty($request->freelancers)) {
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
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'customer_id' => 'required|exists:customers,id',
            'hourly_rate' => 'nullable|numeric',
            'deadline' => 'nullable|date',
            'users' => 'required|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.hourly_rate' => 'required|numeric|min:0',
        ]);

        $deadline = $validated['deadline'] ?? null;
        if (is_string($deadline)) {
            $validated['deadline'] = Carbon::parse($deadline);
        }

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'customer_id' => $validated['customer_id'],
            'hourly_rate' => $validated['hourly_rate'],
            'deadline' => $validated['deadline'] ?? null,
        ]);

        // Attach users with their hourly rates and send notifications
        $assignments = is_array($validated['users'] ?? null) ? $validated['users'] : [];
        foreach ($assignments as $userData) {
            if (! is_array($userData) || ! is_numeric($userData['id'] ?? null) || ! is_numeric($userData['hourly_rate'] ?? null)) {
                continue;
            }
            $userId = (int) $userData['id'];
            $userRate = $userData['hourly_rate'];

            $project->users()->attach($userId, [
                'hourly_rate' => $userRate,
            ]);

            // Send notification to the assigned user
            $user = User::find($userId);
            if ($user) {
                try {
                    $user->notify(new ProjectAssigned($project->load('customer'), $userRate));
                } catch (\Throwable $e) {
                    // A failing mail transport must not fail project creation
                    Log::warning('Project assignment notification failed', [
                        'user_id' => $user->id,
                        'project_id' => $project->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json($project->load('customer', 'users'), 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $this->validated($request, [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'hourly_rate' => 'nullable|numeric',
            'deadline' => 'nullable|date',
            'users' => 'sometimes|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.hourly_rate' => 'required|numeric|min:0',
        ]);

        $deadline = $validated['deadline'] ?? null;
        if (is_string($deadline)) {
            $validated['deadline'] = Carbon::parse($deadline);
        }

        $project->update([
            'name' => $validated['name'] ?? $project->name,
            'description' => $validated['description'] ?? $project->description,
            'customer_id' => $validated['customer_id'] ?? $project->customer_id,
            'hourly_rate' => $validated['hourly_rate'] ?? $project->hourly_rate,
            'deadline' => $validated['deadline'] ?? $project->deadline,
        ]);

        // Update users if provided
        $assignments = $validated['users'] ?? null;
        if (is_array($assignments)) {
            // Get current user IDs before detaching
            $previousUserIds = $project->users()->pluck('users.id')->toArray();

            $project->users()->detach();
            foreach ($assignments as $userData) {
                if (! is_array($userData) || ! is_numeric($userData['id'] ?? null) || ! is_numeric($userData['hourly_rate'] ?? null)) {
                    continue;
                }
                $userId = (int) $userData['id'];
                $userRate = $userData['hourly_rate'];

                $project->users()->attach($userId, [
                    'hourly_rate' => $userRate,
                ]);

                // Only send notification to newly assigned users (not previously assigned)
                if (! in_array($userId, $previousUserIds)) {
                    $user = User::find($userId);
                    if ($user) {
                        try {
                            $user->notify(new ProjectAssigned($project->load('customer'), $userRate));
                        } catch (\Throwable $e) {
                            // A failing mail transport must not fail project update
                            Log::warning('Project assignment notification failed', [
                                'user_id' => $user->id,
                                'project_id' => $project->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        return response()->json($project->load('customer', 'users'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(null, 204);
    }
}
