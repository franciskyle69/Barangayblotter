<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/**
 * Base class for queued jobs that must execute within a specific tenant's
 * database context. Subclasses receive `$tenantId` as a scalar (NOT a Tenant
 * model) so queue serialization never touches the tenant DB — the worker
 * process may not have the tenant connection configured at rehydration time.
 *
 * When the worker finally runs the job, we resolve the Tenant from the
 * central DB, activate its connection, and invoke `handleForTenant()`. The
 * connection is always reset in the `finally` of `runInTenantContext` so
 * nothing leaks into subsequent jobs.
 */
abstract class TenantAwareJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public int $tenantId)
    {
    }

    /**
     * Final to prevent subclasses from forgetting to route through
     * runInTenantContext. If you need pre-tenant setup, override
     * handleForTenant().
     */
    final public function handle(TenantDatabaseManager $tenantDatabases): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            // Tenant was deleted between dispatch and run — drop silently.
            return;
        }

        if (!$tenant->is_active) {
            // Refuse to run against a deactivated tenant. We throw so the
            // queue retries/alerts; silent-drop would hide a real operator
            // mistake (re-activate the tenant vs. let the job fail).
            throw new RuntimeException(sprintf(
                'Refusing to run %s for deactivated tenant [%d].',
                static::class,
                $this->tenantId,
            ));
        }

        $tenantDatabases->runInTenantContext($tenant, function (Tenant $tenant) {
            $this->handleForTenant($tenant);
        });
    }

    abstract protected function handleForTenant(Tenant $tenant): void;
}
