<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tenancy must be resolved as early as possible so that:
        // - the auth guard loads users from the correct tenant DB
        // - session/auth state is consistent before route middleware runs
        $middleware->web(prepend: [
            \App\Http\Middleware\ResolveTenantFromDomain::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'tenant' => \App\Http\Middleware\SetTenantFromSession::class,
            'tenant.ensure' => \App\Http\Middleware\EnsureUserBelongsToTenant::class,
            'tenant.db' => \App\Http\Middleware\SwitchTenantDatabase::class,
            'tenant.role' => \App\Http\Middleware\EnsureTenantRole::class,
            'tenant.permission' => \App\Http\Middleware\EnsureTenantPermission::class,
            // Enforces that an authenticated session is bound to the
            // tenant it was issued for (see middleware docblock). Apply
            // this AFTER tenant resolution (`tenant`/`tenant.db`) so the
            // `current_tenant` binding and tenant DB connection are
            // available when we cross-check.
            'tenant.session.binding' => \App\Http\Middleware\VerifyTenantSessionBinding::class,
            'super_admin' => \App\Http\Middleware\SuperAdminOnly::class,
            'password.change' => \App\Http\Middleware\EnforcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
