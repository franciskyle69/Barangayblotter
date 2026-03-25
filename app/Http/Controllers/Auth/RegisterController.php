<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'requested_role' => ['nullable', Rule::in([
                User::ROLE_CITIZEN,
                User::ROLE_PUROK_SECRETARY,
                User::ROLE_PUROK_LEADER,
                User::ROLE_COMMUNITY_WATCH,
            ])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);

        // If tenant was resolved from domain, auto-assign user to that tenant
        if (app()->bound('current_tenant')) {
            $tenant = app('current_tenant');
            $requestedRole = $validated['requested_role'] ?? User::ROLE_CITIZEN;
            $effectiveRole = $requestedRole === User::ROLE_CITIZEN
                ? User::ROLE_CITIZEN
                : User::ROLE_CITIZEN;

            $user->tenants()->attach($tenant->id, ['role' => $effectiveRole]);
            session(['current_tenant_id' => $tenant->id]);

            if ($requestedRole !== User::ROLE_CITIZEN) {
                return redirect()->route('dashboard')->with(
                    'warning',
                    'Your account was created as Citizen. Requested elevated role requires super admin approval.'
                );
            }

            return redirect()->route('dashboard');
        }

        return redirect()->route('tenant.select');
    }
}
