<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'is_super_admin' => $request->user()->is_super_admin,
                ] : null,
            ],
            'current_tenant' => function () use ($request) {
                if (!app()->bound('current_tenant')) {
                    return null;
                }
                $tenant = app('current_tenant');
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'subdomain' => $tenant->subdomain,
                    'custom_domain' => $tenant->custom_domain,
                    'plan' => [
                        'name' => $tenant->plan->name,
                        'mediation_scheduling' => $tenant->plan->mediation_scheduling,
                    ],
                ];
            },
            'current_tenant_role' => function () use ($request) {
                if (!$request->user() || !app()->bound('current_tenant')) {
                    return null;
                }
                return $request->user()->roleIn(app('current_tenant'));
            },
            'tenant_resolved_from' => fn () => app()->bound('tenant_resolved_from') ? app('tenant_resolved_from') : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
            'app_name' => config('app.name'),
            'logo_url' => '/images/logo.png',
        ];
    }
}
