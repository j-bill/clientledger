<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user->makeVisible('avatar'));
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            // Base64 data URI, capped so a single avatar cannot bloat every
            // response that embeds the user.
            'avatar' => 'nullable|string|max:1048576',
            'notify_on_project_assignment' => 'nullable|boolean',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:16|confirmed',
        ]);

        // Update basic profile information
        $user->name = $request->string('name')->toString();
        $user->email = $request->string('email')->toString();

        if ($request->has('avatar')) {
            $avatar = $request->input('avatar');
            $user->avatar = is_string($avatar) ? $avatar : null;
        }

        if ($request->has('notify_on_project_assignment')) {
            $user->notify_on_project_assignment = $request->boolean('notify_on_project_assignment');
        }

        // Handle password change
        if ($request->filled('current_password') && $request->filled('new_password')) {
            // Verify current password
            if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',
                    'errors' => [
                        'current_password' => ['The current password is incorrect'],
                    ],
                ], 422);
            }

            // Update password
            $user->password = Hash::make($request->string('new_password')->toString());
        }

        $user->save();

        return response()->json($user->makeVisible('avatar'));
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        // Get work logs for this user
        $workLogs = WorkLog::where('user_id', $user->id)->get();

        // Calculate total earnings (hours * hourly_rate for each log)
        $totalEarnings = $workLogs->sum(function ($log) {
            $hours = $log->hours_worked ?? 0;
            $rate = $log->hourly_rate ?? $log->user_hourly_rate ?? 0;

            return $hours * $rate;
        });

        $statistics = [
            'total_work_logs' => $workLogs->count(),
            'total_hours' => $workLogs->sum('hours_worked') ?? 0,
            'total_earnings' => $totalEarnings,
            'active_projects' => $user->projects()->count(),
        ];

        // Get monthly stats for current year
        $currentYear = date('Y');
        $monthlyWorkLogs = WorkLog::where('user_id', $user->id)
            ->whereYear('date', $currentYear)
            ->select('id', 'date', 'hours_worked', 'hourly_rate', 'user_hourly_rate')
            ->get()
            ->groupBy(function ($log) {
                return Carbon::parse($log->date)->format('m');
            });

        $monthlyStats = [];
        foreach ($monthlyWorkLogs as $month => $logs) {
            $monthEarnings = $logs->sum(function ($log) {
                $hours = $log->hours_worked ?? 0;
                $rate = $log->hourly_rate ?? $log->user_hourly_rate ?? 0;

                return $hours * $rate;
            });

            $monthlyStats[] = [
                'month' => (int) $month,
                'hours' => $logs->sum('hours_worked'),
                'earnings' => $monthEarnings,
                'logs' => $logs->count(),
            ];
        }

        $statistics['monthly_stats'] = $monthlyStats;

        return response()->json($statistics);
    }

    /**
     * Get user activity data for heatmap (past year)
     */
    public function activity(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        // Get date from one year ago
        $oneYearAgo = now()->subYear()->startOfDay();

        // Get all work logs grouped by date
        $activityData = WorkLog::where('user_id', $user->id)
            ->where('date', '>=', $oneYearAgo)
            ->select('date', DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->toBase()
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });

        return response()->json($activityData);
    }
}
