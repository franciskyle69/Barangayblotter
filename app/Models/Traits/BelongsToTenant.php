<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Provides automatic tenant scoping for models with a tenant_id column.
 *
 * SECURITY MODEL: **deny-by-default**.
 *   - If no tenant context can be resolved, queries return NO rows
 *     (injected as `WHERE 1 = 0`).
 *   - If no tenant context is present, creating a new record throws.
 *
 * This is a defense-in-depth layer on top of the middleware pipeline.
 * Middleware should always establish context before tenant-scoped models
 * are used, but a stray controller, console command, or queue job that
 * bypasses middleware must NOT silently leak all tenants' rows.
 *
 * Escape hatches (for super-admin tooling + migrations):
 *   - Query: `Model::withoutGlobalScope('tenant')`.
 *   - Create: pass an explicit `tenant_id`.
 */
trait BelongsToTenant
{
    use UsesTenantConnection;

    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = null;

            if (app()->bound('current_tenant')) {
                $tenantId = app('current_tenant')->id;
            } elseif (app()->bound('session') && session()->has('current_tenant_id')) {
                $tenantId = (int) session('current_tenant_id');
            }

            if ($tenantId) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    $tenantId,
                );

                return;
            }

            // DENY-BY-DEFAULT: no tenant resolved → return no rows.
            // This is the critical invariant: a developer who forgets to
            // establish context must not accidentally query across tenants.
            // Use `withoutGlobalScope('tenant')` explicitly if that's what
            // you actually want.
            $builder->whereRaw('1 = 0');
        });

        static::creating(function (Model $model) {
            if ($model->tenant_id) {
                return;
            }

            if (app()->bound('current_tenant')) {
                $model->tenant_id = app('current_tenant')->id;
                return;
            }

            if (app()->bound('session') && session()->has('current_tenant_id')) {
                $model->tenant_id = (int) session('current_tenant_id');
                return;
            }

            throw new RuntimeException(sprintf(
                'Refusing to create [%s] without a tenant context. Set tenant_id explicitly, or run inside middleware/runInTenantContext.',
                static::class,
            ));
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
