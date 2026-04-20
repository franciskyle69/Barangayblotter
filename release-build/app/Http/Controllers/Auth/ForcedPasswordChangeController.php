<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcedPasswordChangeController extends Controller
{
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
