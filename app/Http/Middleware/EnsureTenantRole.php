<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        if ($request->user()?->is_super_admin) {
            return $next($request);
        }
        $tenant = app('current_tenant');
        if (!$tenant) {
            return redirect()->route('tenant.select');
        }
        if ($allowedRoles === []) {
            return $next($request);
        }
        if (!$request->user()->hasRoleIn($tenant, $allowedRoles)) {
            abort(403, 'You do not have permission to perform this action.');
        }
        return $next($request);
    }
}
