<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 🔹 Show Login Page
    public function showLogin()
    {
        return view('auth.login'); // resources/views/auth/login.blade.php
    }

    // 🔹 Handle Login
    public function login(Request $request)
    {
        // ✅ Validation
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // ✅ Attempt Login
        if (Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            // 🔐 Success
            return redirect()->route('dashboard'); // change as needed
        }

        // ❌ Failed
        return back()->with('error', 'Invalid credentials')->withInput();
    }

    // 🔹 Logout
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}