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
        $tenantId = session('current_tenant_id');
        if ($tenantId && $request->user()) {
            $tenant = Tenant::find($tenantId);
            if ($tenant && $request->user()->tenants()->where('tenants.id', $tenant->id)->exists()) {
                app()->instance('current_tenant', $tenant);
            }
        }
        return $next($request);
    }
}
