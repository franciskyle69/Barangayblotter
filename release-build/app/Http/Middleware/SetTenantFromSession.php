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
        if ($tenantId && $request->user() && !$request->user()->is_super_admin) {
            $tenant = Tenant::find($tenantId);
            if ($tenant && $tenant->is_active) {
                app()->instance('current_tenant', $tenant);
                app()->instance('tenant_resolved_from', 'session');
                $this->tenantDatabases->activateTenantConnection($tenant);
            }
        }

        return $next($request);
    }
}
