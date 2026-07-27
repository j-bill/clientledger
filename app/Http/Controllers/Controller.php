<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Validate the request and hand the validated input back as a typed array.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validated(Request $request, array $rules): array
    {
        $validated = $request->validate($rules);

        $typed = [];
        foreach (is_array($validated) ? $validated : [] as $key => $value) {
            if (is_string($key)) {
                $typed[$key] = $value;
            }
        }

        return $typed;
    }

    /**
     * The authenticated user. Callers sit behind auth middleware, so a
     * missing user is a hard 401.
     */
    protected function requireUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
