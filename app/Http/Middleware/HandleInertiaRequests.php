<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Memoized reading of `version.txt` (managed by release-please). The
     * file is read once per PHP process instead of on every request — it
     * only changes when a new release is deployed, and at that point the
     * PHP process is replaced by the updater anyway.
     */
    private static ?string $appVersionCache = null;
    private static bool $appVersionResolved = false;

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'csrf_token' => fn () => csrf_token(),
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
            'app_version' => fn() => $this->resolveAppVersion(),
            'central_login_background_url' => fn() => (string) (env('CENTRAL_LOGIN_BACKGROUND_URL', '')),
            'central_login_background_opacity' => fn() => (float) env('CENTRAL_LOGIN_BACKGROUND_OPACITY', 0.45),
            'central_login_background_blur' => fn() => (int) env('CENTRAL_LOGIN_BACKGROUND_BLUR', 0),
            'logo_url' => function () {
                if (app()->bound('current_tenant')) {
                    $tenant = app('current_tenant');
                    return $tenant->logo_url;
                }

                return '/images/logo.png';
            },
        ];
    }

    /**
     * Returns the current app version as a display-friendly string (e.g.
     * "v1.4.0"). Reads `version.txt` at the project root — release-please
     * owns that file, so this is automatically correct after every deploy.
     *
     * The leading "v" is normalized: if the file contains `1.4.0` we emit
     * `v1.4.0`; if it contains `v1.4.0` we emit `v1.4.0` (no double-v).
     * Returns null if the file is missing or unreadable so the UI can
     * gracefully hide the badge during local development.
     */
    private function resolveAppVersion(): ?string
    {
        if (self::$appVersionResolved) {
            return self::$appVersionCache;
        }

        self::$appVersionResolved = true;

        $path = base_path('version.txt');

        if (!is_file($path) || !is_readable($path)) {
            return self::$appVersionCache = null;
        }

        $raw = trim((string) @file_get_contents($path));

        if ($raw === '') {
            return self::$appVersionCache = null;
        }

        // Strip any leading "v"/"V" and re-prefix, so both "1.4.0" and
        // "v1.4.0" normalize to "v1.4.0".
        $normalized = ltrim($raw, 'vV');

        return self::$appVersionCache = 'v' . $normalized;
    }
}
