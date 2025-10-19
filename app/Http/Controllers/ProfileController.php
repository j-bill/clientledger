<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = User::find(Auth::id());
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request)
    {
        $user = User::find(Auth::id());
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|string',
            'notify_on_project_assignment' => 'nullable|boolean',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:16|confirmed',
        ]);

        // Update basic profile information
        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->has('avatar')) {
            $user->avatar = $request->avatar;
        }
        
        if ($request->has('notify_on_project_assignment')) {
            $user->notify_on_project_assignment = $request->notify_on_project_assignment;
        }

        // Handle password change
        if ($request->filled('current_password') && $request->filled('new_password')) {
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',
                    'errors' => [
                        'current_password' => ['The current password is incorrect']
                    ]
                ], 422);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return response()->json($user);
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        
        // Get work logs for this user
        $workLogs = WorkLog::where('user_id', $user->id)->get();
        
        // Calculate total earnings (hours * hourly_rate for each log)
        $totalEarnings = $workLogs->sum(function($log) {
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
            ->groupBy(function($log) {
                return date('m', strtotime($log->date));
            });

        $monthlyStats = [];
        foreach ($monthlyWorkLogs as $month => $logs) {
            $monthEarnings = $logs->sum(function($log) {
                $hours = $log->hours_worked ?? 0;
                $rate = $log->hourly_rate ?? $log->user_hourly_rate ?? 0;
                return $hours * $rate;
            });
            
            $monthlyStats[] = [
                'month' => (int)$month,
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
    public function activity(Request $request)
    {
        $user = Auth::user();
        
        // Get date from one year ago
        $oneYearAgo = now()->subYear()->startOfDay();
        
        // Get all work logs grouped by date
        $activityData = WorkLog::where('user_id', $user->id)
            ->where('date', '>=', $oneYearAgo)
            ->select('date', DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count
                ];
            });

        return response()->json($activityData);
    }
}
