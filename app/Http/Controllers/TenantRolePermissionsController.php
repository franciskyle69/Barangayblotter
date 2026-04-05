<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $centralConnection = config('tenancy.central_connection', 'central');

        $permissions = collect(User::tenantPermissions())
            ->sort()
            ->values();

        $tenantRoles = User::tenantRoles();

        $roles = collect($tenantRoles)
            ->map(function (string $label, string $roleName) use ($tenant, $centralConnection) {
                $tenantScopedPermissions = DB::connection($centralConnection)
                    ->table('tenant_role_permissions')
                    ->where('tenant_id', $tenant->id)
                    ->where('role_name', $roleName)
                    ->orderBy('permission_name')
                    ->pluck('permission_name')
                    ->values();

                if ($tenantScopedPermissions->isEmpty()) {
                    $tenantScopedPermissions = collect(User::tenantPermissionMatrix()[$roleName] ?? [])
                        ->sort()
                        ->values();
                }

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
        $tenant = app('current_tenant');

        if (!$tenant) {
            return back()->with('error', 'No active tenant context was found for this request.');
        }

        $centralConnection = config('tenancy.central_connection', 'central');

        if (!in_array($role, array_keys(User::tenantRoles()), true)) {
            abort(404);
        }

        $allowedPermissions = User::tenantPermissions();

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', Rule::in($allowedPermissions)],
        ]);

        $selectedPermissions = array_values(array_intersect(
            $validated['permissions'] ?? [],
            $allowedPermissions
        ));

        DB::connection($centralConnection)->transaction(function () use ($centralConnection, $tenant, $role, $selectedPermissions): void {
            DB::connection($centralConnection)
                ->table('tenant_role_permissions')
                ->where('tenant_id', $tenant->id)
                ->where('role_name', $role)
                ->delete();

            if ($selectedPermissions !== []) {
                $timestamp = now();
                $rows = array_map(static fn(string $permissionName) => [
                    'tenant_id' => $tenant->id,
                    'role_name' => $role,
                    'permission_name' => $permissionName,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ], $selectedPermissions);

                DB::connection($centralConnection)
                    ->table('tenant_role_permissions')
                    ->insert($rows);
            }
        });

        return back()->with('success', sprintf('%s permissions updated for %s only.', User::tenantRoles()[$role] ?? $role, $tenant->name));
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