<?php

namespace App\Models\Traits;

/**
 * Routes Eloquent models to the `tenant` database connection when a tenant
 * context is active.
 *
 * This trait is DELIBERATELY minimal and side-effect-free:
 *   - It does NOT read the session.
 *   - It does NOT inspect the request (host resolution).
 *   - It does NOT mutate config/database.connections.tenant.
 *   - It does NOT lazily instantiate a Tenant from an ID.
 *
 * All of those responsibilities now live in `TenantDatabaseManager` and the
 * middleware pipeline (`ResolveTenantFromDomain` → `SetTenantFromSession`
 * → `SwitchTenantDatabase`). By the time a tenant-scoped model is queried,
 * the container binding `current_tenant` must already be set AND the
 * `tenant` connection must already be configured.
 *
 * Why this matters: the old version of this trait mutated config on every
 * call to getConnectionName(), which happens hundreds of times per request.
 * It also did session/request lookups from inside Eloquent, which caused
 * subtle bugs in queue workers and CLI contexts where neither exists.
 */
trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        if (app()->bound('current_tenant')) {
            return 'tenant';
        }

        return $this->connection;
    }
}
