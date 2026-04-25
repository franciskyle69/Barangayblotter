<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function showRegistrationForm(): Response
    {
        if (!app()->bound('current_tenant')) {
            abort(403, 'Registration is only available within a barangay tenant portal.');
        }

        return Inertia::render('Auth/Register', [
            'registrationRoleOptions' => [
                User::ROLE_CITIZEN => 'Citizen (default)',
                User::ROLE_PUROK_SECRETARY => 'Barangay Secretary (requires approval)',
                User::ROLE_PUROK_LEADER => 'Barangay Captain (requires approval)',
                User::ROLE_COMMUNITY_WATCH => 'Staff / Community Watch (requires approval)',
            ],
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        if (!app()->bound('current_tenant')) {
            return redirect()->route('login')->with(
                'warning',
                'Central app registration is disabled. Admin accounts are created by a super admin.'
            );
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'requested_role' => [
                'nullable',
                Rule::in([
                    User::ROLE_CITIZEN,
                    User::ROLE_PUROK_SECRETARY,
                    User::ROLE_PUROK_LEADER,
                    User::ROLE_COMMUNITY_WATCH,
                ])
            ],
        ]);

        $tenant = app('current_tenant');
        $requestedRole = $validated['requested_role'] ?? User::ROLE_CITIZEN;
        // Elevated roles always start as Citizen; they require a super
        // admin to promote. We keep `$requestedRole` for the activity log.
        $effectiveRole = User::ROLE_CITIZEN;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $effectiveRole,
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Mirror LoginController: rotate the session id to defend
        // against session fixation, then bind the session to this tenant
        // so VerifyTenantSessionBinding recognises it. Previously this
        // path skipped both steps, so the very next request logged the
        // user out with a `missing_auth_tenant_binding` failure.
        $request->session()->regenerate();
        session([
            'current_tenant_id' => $tenant->id,
            'auth_tenant_id' => $tenant->id,
        ]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.auth.register',
            description: 'Registered a new tenant user account.',
            metadata: [
                'requested_role' => $requestedRole,
                'effective_role' => $effectiveRole,
            ],
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
            actor: $user,
        );

        if ($requestedRole !== User::ROLE_CITIZEN) {
            return redirect()->route('dashboard')->with(
                'warning',
                'Your account was created as Citizen. Requested elevated role requires super admin approval.'
            );
        }

        return redirect()->route('dashboard');
    }
}
