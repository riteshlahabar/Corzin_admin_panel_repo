<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();

            return redirect()
                ->route('login')
                ->with('error', 'Your admin account is inactive. Please contact super admin.');
        }

        if (! $user->role || ! $user->role->is_active) {
            Auth::logout();

            return redirect()
                ->route('login')
                ->with('error', 'No active role is assigned to your account. Please contact super admin.');
        }

        return $next($request);
    }
}
