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
        $middleware->web(append: [
            \App\Http\Middleware\ResolveTenantFromDomain::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'tenant' => \App\Http\Middleware\SetTenantFromSession::class,
            'tenant.ensure' => \App\Http\Middleware\EnsureUserBelongsToTenant::class,
            'tenant.db' => \App\Http\Middleware\SwitchTenantDatabase::class,
            'tenant.role' => \App\Http\Middleware\EnsureTenantRole::class,
            'tenant.permission' => \App\Http\Middleware\EnsureTenantPermission::class,
            'super_admin' => \App\Http\Middleware\SuperAdminOnly::class,
            'password.change' => \App\Http\Middleware\EnforcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
