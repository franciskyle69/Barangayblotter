<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperRolePermissionsController extends Controller
{
    public function index(): Response
    {
        $centralConnection = config('tenancy.central_connection', 'central');

        $permissions = Permission::on($centralConnection)
            ->select('name')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        $roles = Role::on($centralConnection)
            ->select('id', 'name')
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys(User::tenantRoles()))
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) use ($centralConnection) {
                $rolePermissions = DB::connection($centralConnection)
                    ->table('permissions')
                    ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('role_has_permissions.role_id', $role->id)
                    ->orderBy('permissions.name')
                    ->pluck('permissions.name')
                    ->values();

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => User::tenantRoles()[$role->name] ?? $role->name,
                    'permissions' => $rolePermissions,
                ];
            })
            ->values();

        return Inertia::render('Super/RolesPermissions', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionLabels' => $this->permissionLabels($permissions),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $centralConnection = config('tenancy.central_connection', 'central');

        $roleModel = Role::on($centralConnection)
            ->where('id', $role)
            ->where('guard_name', 'web')
            ->firstOrFail();

        if (!in_array($roleModel->name, array_keys(User::tenantRoles()), true)) {
            abort(404);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct'],
        ]);

        $allowedPermissions = User::tenantPermissions();
        $selectedPermissions = array_values(array_intersect(
            $validated['permissions'] ?? [],
            $allowedPermissions
        ));

        DB::connection($centralConnection)->transaction(function () use ($centralConnection, $roleModel, $selectedPermissions): void {
            $permissionIds = DB::connection($centralConnection)
                ->table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $selectedPermissions)
                ->pluck('id')
                ->all();

            DB::connection($centralConnection)
                ->table('role_has_permissions')
                ->where('role_id', $roleModel->id)
                ->delete();

            if ($permissionIds !== []) {
                $rows = array_map(static fn(int $permissionId) => [
                    'permission_id' => $permissionId,
                    'role_id' => $roleModel->id,
                ], $permissionIds);

                DB::connection($centralConnection)
                    ->table('role_has_permissions')
                    ->insert($rows);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return back()->with('success', sprintf('%s permissions updated.', User::tenantRoles()[$roleModel->name] ?? $roleModel->name));
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