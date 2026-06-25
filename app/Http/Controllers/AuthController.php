<?php

namespace App\Http\Controllers;

use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $routeName = AdminAccess::landingRouteFor(Auth::user()) ?? 'dashboard';

            return redirect()->route($routeName);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (! $user || ! $user->is_active || ! $user->role || ! $user->role->is_active) {
                Auth::logout();

                return back()
                    ->with('error', 'Your admin account is inactive. Please contact super admin.')
                    ->withInput();
            }

            $routeName = AdminAccess::landingRouteFor($user);
            if (! $routeName) {
                Auth::logout();

                return back()
                    ->with('error', 'No module is assigned to your account. Please contact super admin.')
                    ->withInput();
            }

            return redirect()->route($routeName);
        }

        return back()->with('error', 'Invalid credentials')->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
