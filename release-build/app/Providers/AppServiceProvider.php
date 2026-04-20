<?php

namespace App\Providers;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $defaultConnection = (string) config('database.default');
        $sourceConnection = $defaultConnection;

        // Updater subprocesses force DB_CONNECTION=central so commands like `down`
        // and `up` use the central DB context. In that case we still need to build
        // the synthetic `central` alias from the original default connection.
        if ($sourceConnection === 'central') {
            $sourceConnection = (string) env('APP_UPDATE_BASE_DB_CONNECTION', '');
        }

        if ($sourceConnection === '' || $sourceConnection === 'central') {
            $sourceConnection = (string) env('DB_CONNECTION', 'sqlite');
        }

        $centralConfig = config('database.connections.central');
        $sourceConfig = config("database.connections.{$sourceConnection}");

        if (!$centralConfig && $sourceConfig) {
            config([
                'database.connections.central' => $sourceConfig,
            ]);
        }

        if (config('database.connections.central')) {
            config([
                'tenancy.central_connection' => 'central',
            ]);
        }
    }

    public function boot(): void
    {
        PreventRequestsDuringMaintenance::except([
            'system/update',
            'system/update/*',
        ]);

        Gate::define('trigger-system-update', function ($user): bool {
            return (bool) ($user?->is_super_admin);
        });

        View::composer('layouts.app', function ($view) {
            $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
            $view->with([
                'navTenant' => $currentTenant,
                'navShowMediations' => $currentTenant && $currentTenant->plan->mediation_scheduling,
            ]);
        });
    }
}
