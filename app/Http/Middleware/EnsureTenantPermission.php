<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    public function handle(Request $request, Closure $next, string ...$requiredPermissions): Response
    {
        $requiredPermissions = array_values(array_filter(array_map('trim', $requiredPermissions)));

        // Fail CLOSED if the middleware was declared without any
        // permissions. Previously this returned `$next($request)` which
        // meant a route like `->middleware('tenant.permission')` (typo /
        // accidental) was silently unguarded. A middleware designed to
        // enforce permissions that allows the request when no permission
        // is given is a misconfiguration magnet, not a feature.
        if ($requiredPermissions === []) {
            abort(500, 'tenant.permission middleware requires at least one permission argument.');
        }

        $user = $request->user();

        if ($user?->is_super_admin) {
            return $next($request);
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        if (!$tenant) {
            return redirect()->route('tenant.select');
        }

        if (!$user || !$user->hasTenantPermission($tenant, $requiredPermissions)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
