<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CentralAuthController extends Controller
{
    public function showLogin()
    {
        return view('central.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = strtolower(trim($credentials['email']));
        $credentials['is_active'] = true;

        if (!Auth::guard('central')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Identifiants invalides ou compte désactivé.']);
        }

        $request->session()->regenerate();
        $request->user('central')->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('central.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('central')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('central.login');
    }
}
