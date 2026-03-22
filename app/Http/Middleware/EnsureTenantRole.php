<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    public function __construct(
        private readonly array $allowedRoles
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_super_admin) {
            return $next($request);
        }
        $tenant = app('current_tenant');
        if (!$tenant) {
            return redirect()->route('tenant.select');
        }
        if (!$request->user()->hasRoleIn($tenant, $this->allowedRoles)) {
            abort(403, 'You do not have permission to perform this action.');
        }
        return $next($request);
    }
}
