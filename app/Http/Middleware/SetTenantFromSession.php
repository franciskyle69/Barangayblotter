<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tenant already resolved from domain
        if (app()->bound('current_tenant')) {
            return $next($request);
        }

        $tenantId = session('current_tenant_id');
        if ($tenantId && $request->user()) {
            $tenant = Tenant::find($tenantId);
            if ($tenant && $tenant->is_active && $request->user()->tenants()->where('tenants.id', $tenant->id)->exists()) {
                app()->instance('current_tenant', $tenant);
                app()->instance('tenant_resolved_from', 'session');
            }
        }

        return $next($request);
    }
}
