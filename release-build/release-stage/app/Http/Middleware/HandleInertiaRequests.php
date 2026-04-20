<?php

namespace App\Http\Middleware;

use App\Models\User;
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
                    'must_change_password' => (bool) $request->user()->must_change_password,
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
                    'sidebar_label' => $tenant->getRawOriginal('sidebar_label') ?: $tenant->slug ?: $tenant->name,
                    'logo_url' => $tenant->logo_url,
                    'theme_preset' => $tenant->theme_preset,
                    'theme_primary_color' => $tenant->theme_primary_color,
                    'theme_bg_color' => $tenant->theme_bg_color,
                    'theme_sidebar_color' => $tenant->theme_sidebar_color,
                    'theme_css_variables' => $tenant->themeCssVariables(),
                    'login_background_url' => $tenant->login_background_url,
                    'login_background_opacity' => (float) ($tenant->login_background_opacity ?? 0.45),
                    'login_background_blur' => (int) ($tenant->login_background_blur ?? 0),
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
            'tenant_permissions' => function () use ($request) {
                if (!$request->user() || !app()->bound('current_tenant')) {
                    return [];
                }

                $tenant = app('current_tenant');
                $user = $request->user();

                $permissions = [];
                foreach (User::tenantPermissions() as $permission) {
                    $permissions[$permission] = $user->hasTenantPermission($tenant, $permission);
                }

                return $permissions;
            },
            'tenant_resolved_from' => fn() => app()->bound('tenant_resolved_from') ? app('tenant_resolved_from') : null,
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
            ],
            'errors' => fn() => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
            'app_name' => config('app.name'),
            'logo_url' => function () {
                if (app()->bound('current_tenant')) {
                    $tenant = app('current_tenant');
                    return $tenant->logo_url;
                }

                return '/images/logo.png';
            },
        ];
    }
}
