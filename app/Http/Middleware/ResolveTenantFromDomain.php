<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tenant already resolved (e.g. from session in a previous middleware)
        if (app()->bound('current_tenant')) {
            return $next($request);
        }

        $host = $request->getHost();
        $baseDomain = strtolower(config('tenancy.base_domain', 'localhost'));

        // Skip resolution for the exact base domain (no subdomain)
        $hostWithoutPort = strtolower(preg_replace('/:\d+$/', '', $host));
        if ($hostWithoutPort === $baseDomain || $hostWithoutPort === 'www.' . $baseDomain) {
            return $next($request);
        }

        // Skip reserved super admin subdomain
        $superSub = config('tenancy.super_admin_subdomain', 'admin');
        if ($superSub && str_starts_with($hostWithoutPort, $superSub . '.' . $baseDomain)) {
            return $next($request);
        }

        $tenant = Tenant::resolveFromHost($host);

        if ($tenant) {
            if (!$tenant->is_active) {
                abort(403, 'This barangay account has been deactivated. Please contact the city administrator.');
            }

            app()->instance('current_tenant', $tenant);
            session(['current_tenant_id' => $tenant->id]);

            // Share that tenant was resolved from domain (not session)
            app()->instance('tenant_resolved_from', 'domain');
        }

        return $next($request);
    }
}
