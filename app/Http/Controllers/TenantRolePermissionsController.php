<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                $override = DB::table('tenant_role_permission_overrides')
                    ->where('role_name', $roleName)
                    ->value('permissions');

                $storedPermissions = null;
                if ($override !== null) {
                    $decoded = json_decode((string) $override, true);
                    if (is_array($decoded)) {
                        $storedPermissions = $decoded;
                    }
                }

                $tenantScopedPermissions = collect($storedPermissions ?? (User::tenantPermissionMatrix()[$roleName] ?? []))
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

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct'],
        ]);

        $allowedPermissions = User::tenantPermissions();
        $selectedPermissions = array_values(array_intersect(
            $validated['permissions'] ?? [],
            $allowedPermissions
        ));

        DB::table('tenant_role_permission_overrides')->updateOrInsert(
            ['role_name' => $role],
            [
                'permissions' => json_encode(array_values($selectedPermissions)),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return back()->with('success', sprintf('%s permissions updated for this tenant.', User::tenantRoles()[$role] ?? $role));
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