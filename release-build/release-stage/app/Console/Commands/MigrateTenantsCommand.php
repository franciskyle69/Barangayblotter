<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Console\Command;

class MigrateTenantsCommand extends Command
{
    protected $signature = 'tenants:migrate {--tenant= : Specific tenant ID to migrate} {--force : Skip confirmation prompt}';

    protected $description = 'Run tenant-specific migrations for one or all tenant databases';

    public function handle(TenantDatabaseManager $tenantDatabases): int
    {
        $tenantId = $this->option('tenant');

        $tenants = Tenant::query()
            ->when($tenantId, fn($query) => $query->whereKey($tenantId))
            ->whereNotNull('database_name')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants with provisioned databases were found.');
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $target = $tenantId ? "tenant {$tenantId}" : 'all tenants';
            if (!$this->confirm("Run tenant migrations for {$target}?", true)) {
                $this->info('Cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('Running tenant migrations...');

        $successful = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                $tenantDatabases->migrateTenantDatabase($tenant);
                $this->line("  [OK] Tenant {$tenant->id} ({$tenant->name})");
                $successful++;
            } catch (\Throwable $e) {
                $this->error("  [FAIL] Tenant {$tenant->id} ({$tenant->name}): {$e->getMessage()}");
                $failed++;
            } finally {
                $tenantDatabases->resetToCentralConnection();
            }
        }

        $this->newLine();
        $this->info("Tenant migration complete: {$successful} successful, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
