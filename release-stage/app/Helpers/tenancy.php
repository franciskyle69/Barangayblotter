<?php

/**
 * Tenancy Helper Functions
 * 
 * Global helper functions for accessing the tenancy manager and current tenant.
 */

if (!function_exists('tenancy')) {
    /**
     * Get the Tenancy Manager instance
     * 
     * @return \App\Services\TenancyManager
     */
    function tenancy()
    {
        return app('tenancy_manager');
    }
}

if (!function_exists('tenant')) {
    /**
     * Get the current tenant or a value from the current tenant
     * 
     * @param string|null $key Optional key to retrieve from current tenant
     * @param mixed $default Default value if key not found
     * @return \App\Models\Tenant|mixed|null
     */
    function tenant($key = null, $default = null)
    {
        $manager = app('tenancy_manager');
        $tenantModel = $manager->current();

        if ($key && $tenantModel) {
            return $tenantModel->{$key} ?? $default;
        }

        return $tenantModel;
    }
}
