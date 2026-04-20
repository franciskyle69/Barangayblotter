<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $user = $request->user();

        return Inertia::render('Tenant/Settings', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        $user->update($validated);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.settings.profile_update',
            description: "Updated account profile for {$user->name}.",
            metadata: [
                'before' => $before,
                'after' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ],
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', 'Account profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => bcrypt($validated['password']),
        ]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.settings.password_update',
            description: "Changed account password for {$user->name}.",
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', 'Password changed successfully.');
    }
}
