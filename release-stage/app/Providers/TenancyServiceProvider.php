<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Tenant as TenantModel;

/**
 * Tenancy Service Provider
 *
 * Provides shallow integration between our custom middleware and the Tenancy framework.
 * This allows us to keep our proven middleware while leveraging Tenancy's features.
 */
class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tenancy helpers are loaded via composer autoload
        // This method is intentionally empty but kept for future expansion
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TenancyManager as singleton
        $this->app->singleton('tenancy_manager', function () {
            return new \App\Services\TenancyManager();
        });

        // Register alias for easy access
        $this->app->alias('tenancy_manager', \App\Services\TenancyManager::class);
    }
}
