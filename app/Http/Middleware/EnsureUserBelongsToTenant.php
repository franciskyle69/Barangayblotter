<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_super_admin) {
            return $next($request);
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (!$tenant) {
            return redirect()->route('tenant.select')
                ->with('warning', 'Please select a barangay to continue.');
        }

        if (!$tenant->is_active) {
            abort(403, 'This barangay account has been deactivated.');
        }

        $belongs = $request->user()->tenants()->where('tenants.id', $tenant->id)->exists();
        if (!$belongs) {
            // When tenant was resolved from domain, show error (can't switch tenants)
            $resolvedFrom = app()->bound('tenant_resolved_from') ? app('tenant_resolved_from') : 'session';
            if ($resolvedFrom === 'domain') {
                abort(403, 'You do not have access to this barangay.');
            }
            // When from session, redirect to tenant select
            session()->forget('current_tenant_id');
            return redirect()->route('tenant.select')
                ->with('warning', 'You do not belong to that barangay. Please select another.');
        }

        return $next($request);
    }
}
