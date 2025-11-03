<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'role' => 'required|string|in:admin,freelancer',
            'hourly_rate' => 'required|numeric|min:0',
            'notify_on_project_assignment' => 'sometimes|boolean',
            'avatar' => 'nullable|string|max:255',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        
        return User::create($validated);
    }

    public function show(User $user)
    {
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['sometimes', 'confirmed', PasswordRule::defaults()],
            'role' => 'sometimes|string|in:admin,freelancer',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'notify_on_project_assignment' => 'sometimes|boolean',
            'avatar' => 'nullable|string|max:255',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return $user;
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function resetPassword(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent successfully']);
        }

        return response()->json(['message' => 'Unable to send password reset link'], 500);
    }
}
