<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Session;

/**
 * Tenancy Manager
 *
 * Provides Tenancy-compatible interface for accessing and managing current tenant.
 * Works with our existing middleware that stores tenant context in session.
 */
class TenancyManager
{
    /**
     * Get the current tenant
     */
    public function current(): ?Tenant
    {
        $tenantId = Session::get('current_tenant_id');
        if ($tenantId) {
            return Tenant::find($tenantId);
        }
        return null;
    }

    /**
     * Get current tenant ID
     */
    public function getId(): ?string
    {
        return (string) Session::get('current_tenant_id');
    }

    /**
     * Get a value from current tenant
     */
    public function get(string $key, $default = null)
    {
        $tenant = $this->current();
        if ($tenant) {
            return $tenant->{$key} ?? $default;
        }
        return $default;
    }

    /**
     * Get all current tenant data
     */
    public function all(): array
    {
        $tenant = $this->current();
        if ($tenant) {
            return $tenant->toArray();
        }
        return [];
    }

    /**
     * Initialize tenancy for a specific tenant
     * (for running code in a tenant context outside of HTTP)
     */
    public function initialize(Tenant $tenant): void
    {
        Session::put('current_tenant_id', $tenant->id);
    }

    /**
     * End tenancy context
     * (for cleaning up after running code in a tenant context)
     */
    public function end(): void
    {
        Session::forget('current_tenant_id');
    }

    /**
     * Run a closure within a specific tenant context
     */
    public function run(Tenant $tenant, callable $callback)
    {
        $original = Session::get('current_tenant_id');
        
        try {
            $this->initialize($tenant);
            return call_user_func($callback, $tenant);
        } finally {
            if ($original) {
                Session::put('current_tenant_id', $original);
            } else {
                Session::forget('current_tenant_id');
            }
        }
    }
}
