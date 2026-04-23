<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromSession
{
    public function __construct(private TenantDatabaseManager $tenantDatabases)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tenant already resolved from domain
        if (app()->bound('current_tenant')) {
            return $next($request);
        }

        $tenantId = session('current_tenant_id');
        if (!$tenantId || !$request->user() || $request->user()->is_super_admin) {
            return $next($request);
        }

        // Cross-validation: the session must be bound to the same tenant
        // it claims to be switching to. This stops a scenario where a
        // session created on tenant A (auth_tenant_id=A) has somehow had
        // its current_tenant_id mutated to B — which would let middleware
        // below swap to B's DB and serve A's authenticated user as if they
        // were a B user. If the binding is missing or mismatched, drop the
        // tenant resolution and let VerifyTenantSessionBinding handle the
        // forced logout downstream.
        $authBinding = session('auth_tenant_id', false);
        if ($authBinding === false || $authBinding === null || (int) $authBinding !== (int) $tenantId) {
            return $next($request);
        }

        $tenant = Tenant::find($tenantId);
        if ($tenant && $tenant->is_active) {
            app()->instance('current_tenant', $tenant);
            app()->instance('tenant_resolved_from', 'session');
            $this->tenantDatabases->activateTenantConnection($tenant);
        }

        return $next($request);
    }
}
