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

        $user = $request->user();

        if ($user?->is_super_admin) {
            return $next($request);
        }

        $tenant = app('current_tenant');
        if (!$tenant) {
            return redirect()->route('tenant.select');
        }

        if ($requiredPermissions === []) {
            return $next($request);
        }

        if (!$user || !$user->hasTenantPermission($tenant, $requiredPermissions)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
