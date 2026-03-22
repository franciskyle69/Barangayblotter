<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
