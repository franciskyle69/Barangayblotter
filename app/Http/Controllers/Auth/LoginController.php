<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
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
        $isTenantDomain = app()->bound('current_tenant');

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $tenant = $isTenantDomain ? app('current_tenant') : null;

            // (temporary debugging removed)

            // Tenant domain login policy:
            // - super admins must use central domain
            if ($isTenantDomain) {
                if ($user->is_super_admin) {
                    ActivityLogService::record(
                        request: $request,
                        action: 'tenant.auth.denied_super_admin',
                        description: 'Denied tenant-domain login for super admin account.',
                        metadata: ['email' => $user->email],
                        targetType: 'user',
                        targetId: $user->id,
                        tenantId: $tenant->id,
                    );

                    Auth::logout();
                    return back()->withErrors([
                        'email' => 'Super admins must sign in from the central admin domain.',
                    ])->onlyInput('email');
                }

                session([
                    'current_tenant_id' => $tenant->id,
                    // Bind the authenticated session to this tenant so the
                    // VerifyTenantSessionBinding middleware can detect and
                    // reject cross-tenant cookie replay attempts.
                    'auth_tenant_id' => $tenant->id,
                ]);

                // (temporary debugging removed)

                ActivityLogService::record(
                    request: $request,
                    action: 'tenant.auth.login',
                    description: 'Tenant user signed in via tenant portal.',
                    metadata: ['email' => $user->email],
                    targetType: 'user',
                    targetId: $user->id,
                    tenantId: $tenant->id,
                    actor: $user,
                );

                // Use a path-based redirect so tenant domains (e.g. *.lvh.me)
                // don't get sent back to APP_URL's host (often localhost).
                return redirect()->intended('/dashboard');
            }

            // Central domain login policy:
            // - only super admins can sign in here
            if (!$user->is_super_admin) {
                ActivityLogService::record(
                    request: $request,
                    action: 'super.auth.denied_non_super',
                    description: 'Denied central login for non-super-admin account.',
                    metadata: ['email' => $user->email],
                    targetType: 'user',
                    targetId: $user->id,
                );

                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tenant users must sign in from their barangay domain.',
                ])->onlyInput('email');
            }

            // Super admins carry a null tenant binding — their sessions
            // are valid only on the central domain. VerifyTenantSessionBinding
            // will reject the session if a tenant ever resolves for it.
            session(['auth_tenant_id' => null]);

            ActivityLogService::record(
                request: $request,
                action: 'super.auth.login',
                description: 'Super admin signed in to central app.',
                metadata: ['email' => $user->email],
                targetType: 'user',
                targetId: $user->id,
                actor: $user,
            );

            // Super admin → city dashboard
            return redirect()->intended('/super/dashboard');
        }

        if (!$isTenantDomain) {
            ActivityLogService::record(
                request: $request,
                action: 'super.auth.login_failed',
                description: 'Failed central login attempt.',
                metadata: ['email' => $request->input('email')],
            );
        } else {
            $tenant = app('current_tenant');

            ActivityLogService::record(
                request: $request,
                action: 'tenant.auth.login_failed',
                description: 'Failed tenant login attempt.',
                metadata: ['email' => $request->input('email')],
                tenantId: $tenant?->id,
            );
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = (int) $request->session()->get('current_tenant_id');

        if ($user?->is_super_admin && !app()->bound('current_tenant')) {
            ActivityLogService::record(
                request: $request,
                action: 'super.auth.logout',
                description: 'Super admin signed out from central app.',
                metadata: ['email' => $user->email],
                targetType: 'user',
                targetId: $user->id,
                actor: $user,
            );
        } elseif ($user && $tenantId > 0) {
            ActivityLogService::record(
                request: $request,
                action: 'tenant.auth.logout',
                description: 'Tenant user signed out from tenant portal.',
                metadata: ['email' => $user->email],
                targetType: 'user',
                targetId: $user->id,
                tenantId: $tenantId,
                actor: $user,
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
