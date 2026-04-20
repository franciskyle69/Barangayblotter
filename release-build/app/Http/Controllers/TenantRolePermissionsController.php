<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantRolePermissionsController extends Controller
{
    public function index(): Response
    {
        $tenant = app('current_tenant');

        if (!$tenant) {
            abort(404);
        }

        $permissions = collect(User::tenantPermissions())
            ->sort()
            ->values();

        $tenantRoles = User::tenantRoles();

        $roles = collect($tenantRoles)
            ->map(function (string $label, string $roleName) {
                $tenantScopedPermissions = collect(User::tenantPermissionMatrix()[$roleName] ?? [])
                    ->sort()
                    ->values();

                return [
                    'id' => $roleName,
                    'name' => $roleName,
                    'label' => $label,
                    'permissions' => $tenantScopedPermissions,
                ];
            })
            ->values();

        return Inertia::render('Tenant/RolesPermissions', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionLabels' => $this->permissionLabels($permissions),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        if (!in_array($role, array_keys(User::tenantRoles()), true)) {
            abort(404);
        }

        return back()->with('warning', 'Tenant-specific permission overrides are not stored centrally in the tenant-local architecture.');
    }

    private function permissionLabels($permissions): array
    {
        return collect($permissions)
            ->mapWithKeys(fn(string $permission) => [
                $permission => ucwords(str_replace('_', ' ', $permission)),
            ])
            ->all();
    }
}