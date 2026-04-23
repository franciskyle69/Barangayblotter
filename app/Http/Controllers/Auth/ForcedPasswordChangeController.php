<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForcedPasswordChangeController extends Controller
{
    /**
     * Full-page forced-password-change screen. Users with
     * `must_change_password = true` cannot navigate anywhere else
     * (`EnforcePasswordChange` middleware redirects them here). This is
     * intentionally a standalone page, not a layout with sidebar nav —
     * loading tenant data before the password is rotated would leak it
     * into the browser even if the modal happens to fail to render.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ForcedPasswordChange', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        $tenantId = app()->bound('current_tenant') ? app('current_tenant')->id : null;

        ActivityLogService::record(
            request: $request,
            action: $user->is_super_admin ? 'super.auth.force_password_change' : 'tenant.auth.force_password_change',
            description: "Completed required password change for {$user->name}.",
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenantId,
            actor: $user,
        );

        return back()->with('success', 'Password updated successfully. You now have full access.');
    }
}
