<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    public function __construct(private TenantDatabaseManager $tenantDatabases)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tenant already resolved (e.g. from session in a previous middleware)
        if (app()->bound('current_tenant')) {
            return $next($request);
        }

        $host = $request->getHost();
        $baseDomains = $this->centralHosts();

        // Skip resolution for the exact base domain (no subdomain)
        $hostWithoutPort = strtolower(preg_replace('/:\d+$/', '', $host));
        if (in_array($hostWithoutPort, $baseDomains, true) || in_array($hostWithoutPort, array_map(static fn(string $baseDomain) => 'www.' . $baseDomain, $baseDomains), true)) {
            return $next($request);
        }

        // Skip reserved super admin subdomain
        $superSub = config('tenancy.super_admin_subdomain', 'admin');
        foreach ($baseDomains as $baseDomain) {
            if ($superSub && str_starts_with($hostWithoutPort, $superSub . '.' . $baseDomain)) {
                return $next($request);
            }
        }

        $tenant = Tenant::resolveFromHost($host);

        if ($tenant) {
            if (!$tenant->is_active) {
                abort(403, 'This barangay account has been deactivated. Please contact the city administrator.');
            }

            app()->instance('current_tenant', $tenant);
            session(['current_tenant_id' => $tenant->id]);
            $this->tenantDatabases->activateTenantConnection($tenant);

            // Share that tenant was resolved from domain (not session)
            app()->instance('tenant_resolved_from', 'domain');
        } else {
            abort(404, 'Tenant not found for this subdomain.');
        }

        return $next($request);
    }

    /**
     * Hosts that should always be treated as the central app.
     *
     * @return array<int, string>
     */
    private function centralHosts(): array
    {
        $hosts = [
            strtolower((string) config('tenancy.base_domain', 'localhost')),
            'localhost',
            '127.0.0.1',
        ];

        $appHost = parse_url((string) config('app.url', ''), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = strtolower($appHost);
        }

        return array_values(array_unique(array_filter($hosts)));
    }
}
