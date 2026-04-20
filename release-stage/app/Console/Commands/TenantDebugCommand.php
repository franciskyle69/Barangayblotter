<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantDebugCommand extends Command
{
    protected $signature = 'tenant:debug {tenant? : Tenant ID or slug to inspect}';

    protected $description = 'Print active tenant and current DB connection details for tenancy debugging';

    public function __construct(private TenantDatabaseManager $tenantDatabases)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantArg = $this->argument('tenant');

        if ($tenantArg !== null) {
            $tenant = Tenant::query()
                ->whereKey($tenantArg)
                ->orWhere('slug', $tenantArg)
                ->first();

            if (!$tenant) {
                $this->error("Tenant [{$tenantArg}] not found.");
                return self::FAILURE;
            }

            app()->instance('current_tenant', $tenant);
            app()->instance('tenant_resolved_from', 'cli');

            try {
                $this->tenantDatabases->activateTenantConnection($tenant);
            } catch (\Throwable $e) {
                $this->error('Failed to activate tenant connection: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $resolvedFrom = app()->bound('tenant_resolved_from') ? app('tenant_resolved_from') : 'n/a';
        $defaultConnection = DB::getDefaultConnection();
        $configuredDefault = config('database.default');
        $centralConnection = config('tenancy.central_connection', 'central');
        $centralDatabase = config("database.connections.{$centralConnection}.database");
        $tenantDatabase = config('database.connections.tenant.database');
        $tenantBinding = app()->bound('tenant_connection_name') ? app('tenant_connection_name') : 'n/a';

        $this->line('TENANCY DEBUG SNAPSHOT');
        $this->line(str_repeat('-', 72));

        if ($tenant) {
            $this->table(['Tenant Field', 'Value'], [
                ['Tenant ID', (string) $tenant->id],
                ['Tenant Name', (string) $tenant->name],
                ['Tenant Slug', (string) $tenant->slug],
                ['Tenant Active', $tenant->is_active ? 'yes' : 'no'],
                ['Tenant Database Name', (string) ($tenant->database_name ?? 'n/a')],
                ['Tenant Resolved From', (string) $resolvedFrom],
            ]);
        } else {
            $this->warn('No active tenant is currently bound in the application container.');
        }

        $this->table(['Connection Field', 'Value'], [
            ['Configured Default Connection', (string) $configuredDefault],
            ['Runtime Default Connection', (string) $defaultConnection],
            ['Central Connection Alias', (string) $centralConnection],
            ['Central Database', (string) ($centralDatabase ?? 'n/a')],
            ['Tenant Connection Binding', (string) $tenantBinding],
            ['Tenant Configured Database', (string) ($tenantDatabase ?? 'n/a')],
        ]);

        return self::SUCCESS;
    }
}
