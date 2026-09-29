<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'identifier.required' => 'Enter your username or email address.',
            'password.required' => 'Enter your password.',
        ]);

        $field = filter_var($credentials['identifier'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        if (! Auth::attempt([
            $field => $credentials['identifier'],
            'password' => $credentials['password'],
        ])) {
            return back()
                ->withErrors(['identifier' => 'Those credentials do not match our records.'])
                ->withInput($request->only('identifier'));
        }

        $request->session()->regenerate();

        return redirect()->intended($request->user()->role === 'superadmin'
            ? route('superadmin.dashboard')
            : route('pos'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}