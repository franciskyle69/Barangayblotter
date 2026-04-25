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
        $this->assertSessionCookieScope();

        PreventRequestsDuringMaintenance::except([
            'system/update',
            'system/update/*',
        ]);

        Gate::define('trigger-system-update', function ($user): bool {
            return (bool) ($user?->is_super_admin);
        });

        Gate::define('publish-releases', function ($user): bool {
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

    /**
     * Enforces a safe `session.domain` configuration at boot.
     *
     * A leading-dot cookie domain (e.g. `.example.com`) is shared across
     * all subdomains — which means a session cookie issued on tenant A's
     * subdomain would also be sent to tenant B's subdomain, enabling
     * cross-tenant session replay. For a multi-tenant app with one tenant
     * per subdomain, the cookie MUST be host-only (SESSION_DOMAIN=null).
     *
     * In production we fail hard rather than boot into an insecure state.
     * In non-production we only log a warning so local dev isn't blocked.
     */
    private function assertSessionCookieScope(): void
    {
        $domain = config('session.domain');

        if (!is_string($domain) || $domain === '') {
            return;
        }

        if (!str_starts_with($domain, '.')) {
            return;
        }

        $message = sprintf(
            'Unsafe session.domain [%s]: a leading-dot domain lets one tenant subdomain receive another tenant\'s session cookie. Set SESSION_DOMAIN=null for host-only cookies.',
            $domain,
        );

        if ($this->app->environment('production')) {
            throw new \RuntimeException($message);
        }

        if (function_exists('logger')) {
            logger()->warning($message);
        }
    }
}
