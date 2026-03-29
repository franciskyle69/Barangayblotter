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

            // Tenant domain login policy:
            // - super admins must use central domain
            // - tenant users must belong to this resolved tenant
            if ($isTenantDomain) {
                $tenant = app('current_tenant');

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

                if ($user->tenants()->where('tenants.id', $tenant->id)->exists()) {
                    session(['current_tenant_id' => $tenant->id]);

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

                    return redirect()->intended(route('dashboard'));
                }

                // User doesn't belong to this tenant's domain
                ActivityLogService::record(
                    request: $request,
                    action: 'tenant.auth.denied_not_member',
                    description: 'Denied tenant-domain login for user not assigned to tenant.',
                    metadata: ['email' => $user->email],
                    targetType: 'user',
                    targetId: $user->id,
                    tenantId: $tenant->id,
                );

                Auth::logout();
                return back()->withErrors([
                    'email' => 'You do not have access to this barangay.',
                ])->onlyInput('email');
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
            return redirect()->intended(route('super.dashboard'));
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
