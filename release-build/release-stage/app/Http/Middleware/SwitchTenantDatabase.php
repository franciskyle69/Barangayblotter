<?php

namespace App\Http\Middleware;

use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SwitchTenantDatabase
{
    public function __construct(private TenantDatabaseManager $tenantDatabases)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->bound('current_tenant')) {
            return $next($request);
        }

        $tenant = app('current_tenant');

        if (!$tenant->is_active) {
            abort(403, 'This barangay account has been deactivated.');
        }

        if (empty($tenant->database_name)) {
            abort(503, 'Tenant database is not configured. Please contact support.');
        }

        try {
            $this->tenantDatabases->activateTenantConnection($tenant);
        } catch (Throwable $e) {
            report($e);
            abort(503, 'Unable to initialize tenant database connection.');
        }

        return $next($request);
    }
}
