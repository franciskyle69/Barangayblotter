<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $defaultConnection = config('database.default');
        $defaultConfig = config("database.connections.{$defaultConnection}");

        if ($defaultConfig) {
            config([
                'database.connections.central' => $defaultConfig,
                'tenancy.central_connection' => 'central',
            ]);
        }
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
            $view->with([
                'navTenant' => $currentTenant,
                'navShowMediations' => $currentTenant && $currentTenant->plan->mediation_scheduling,
            ]);
        });
    }
}
