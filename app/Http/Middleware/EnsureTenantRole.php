<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $allowedRoles = array_values(array_filter(array_map('trim', $allowedRoles)));

        // Fail CLOSED on a misconfigured middleware call — matches the
        // same hardening we applied to tenant.permission. A typo like
        // `->middleware('tenant.role')` with no args must not silently
        // open the route.
        if ($allowedRoles === []) {
            abort(500, 'tenant.role middleware requires at least one role argument.');
        }

        if ($request->user()?->is_super_admin) {
            return $next($request);
        }

        // Use `bound()` so an unset container binding returns a clean
        // redirect instead of an uncaught "Target class not found" error.
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        if (!$tenant) {
            return redirect()->route('tenant.select');
        }

        if (!$request->user() || !$request->user()->hasRoleIn($tenant, $allowedRoles)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
