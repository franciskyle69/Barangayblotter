<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Provides automatic tenant scoping for models with a tenant_id column.
 *
 * - Adds a global scope that filters queries by the current tenant
 * - Auto-sets tenant_id when creating new records
 * - Defines the tenant() relationship
 *
 * Usage: Add `use BelongsToTenant;` to any model with a tenant_id column.
 * Super admin queries can bypass with `Model::withoutGlobalScope('tenant')`.
 */
trait BelongsToTenant
{
    use UsesTenantConnection;

    public static function bootBelongsToTenant(): void
    {
        // Global scope: auto-filter by current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Check for tenant in container (set by middleware)
            if (app()->bound('current_tenant')) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    app('current_tenant')->id
                );
            }
            // Fallback: check session (set by our TenancyManager or middleware)
            elseif (session()->has('current_tenant_id')) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    session('current_tenant_id')
                );
            }
        });

        // Auto-set tenant_id on new records
        static::creating(function (Model $model) {
            if ($model->tenant_id) {
                return; // Already set
            }

            // Try container first (middleware)
            if (app()->bound('current_tenant')) {
                $model->tenant_id = app('current_tenant')->id;
            }
            // Fallback to session (TenancyManager)
            elseif (session()->has('current_tenant_id')) {
                $model->tenant_id = session('current_tenant_id');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
