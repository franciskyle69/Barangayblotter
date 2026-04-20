<?php

namespace App\Models\Traits;

use App\Models\Tenant;

trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        $databaseName = $this->resolveTenantDatabaseName();

        if ($databaseName) {
            $centralConnection = config('tenancy.central_connection', 'central');
            $baseConfig = config("database.connections.{$centralConnection}");

            if (is_array($baseConfig)) {
                $driver = $baseConfig['driver'] ?? null;

                if ($driver === 'sqlite') {
                    $filename = str_ends_with($databaseName, '.sqlite') ? $databaseName : $databaseName . '.sqlite';
                    $tenantDatabase = database_path('tenants/' . $filename);
                } else {
                    $tenantDatabase = $databaseName;
                }

                config([
                    'database.connections.tenant' => array_merge($baseConfig, [
                        'database' => $tenantDatabase,
                    ])
                ]);

                return 'tenant';
            }
        }

        return $this->connection;
    }

    private function resolveTenantDatabaseName(): ?string
    {
        if (app()->bound('current_tenant')) {
            return app('current_tenant')->database_name ?? null;
        }

        if (app()->bound('session') && session()->has('current_tenant_id')) {
            $tenant = Tenant::find((int) session('current_tenant_id'));
            if ($tenant) {
                app()->instance('current_tenant', $tenant);
                return $tenant->database_name;
            }
        }

        if (app()->bound('request')) {
            $host = request()->getHost();
            $tenant = Tenant::resolveFromHost($host);
            if ($tenant) {
                app()->instance('current_tenant', $tenant);
                return $tenant->database_name;
            }
        }

        return null;
    }
}
