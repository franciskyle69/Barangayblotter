<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Tenant domain login policy:
            // - super admins must use central domain
            // - tenant users must belong to this resolved tenant
            if (app()->bound('current_tenant')) {
                $tenant = app('current_tenant');

                if ($user->is_super_admin) {
                    Auth::logout();
                    return back()->withErrors([
                        'email' => 'Super admins must sign in from the central admin domain.',
                    ])->onlyInput('email');
                }

                if ($user->tenants()->where('tenants.id', $tenant->id)->exists()) {
                    session(['current_tenant_id' => $tenant->id]);
                    return redirect()->intended(route('dashboard'));
                }

                // User doesn't belong to this tenant's domain
                Auth::logout();
                return back()->withErrors([
                    'email' => 'You do not have access to this barangay.',
                ])->onlyInput('email');
            }

            // Central domain login policy:
            // - only super admins can sign in here
            if (!$user->is_super_admin) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tenant users must sign in from their barangay domain.',
                ])->onlyInput('email');
            }

            // Super admin → city dashboard
            return redirect()->intended(route('super.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
