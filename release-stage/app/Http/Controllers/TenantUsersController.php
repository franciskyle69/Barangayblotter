<?php

namespace App\Http\Controllers;

use App\Mail\TenantUserAccountCreatedMail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TenantUsersController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $users = User::query()
            ->select('id', 'name', 'email', 'phone', 'role', 'is_super_admin', 'must_change_password')
            ->where('is_super_admin', false)
            ->orderBy('name')
            ->get()
            ->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_super_admin' => (bool) $user->is_super_admin,
                'role' => $user->role,
            ])->values();

        return Inertia::render('Tenant/Users', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'users' => $users,
            'availableUsers' => [],
            'roles' => User::tenantRoles(),
        ]);
    }

    public function addTenantUser(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $plainPassword = str()->random(16);
            $user = User::create([
                'name' => strtok($validated['email'], '@') ?: $validated['email'],
                'email' => $validated['email'],
                'password' => Hash::make($plainPassword),
                'role' => $validated['role'],
                'is_super_admin' => false,
                'must_change_password' => true,
            ]);

            try {
                Mail::to($user->email)->send(
                    new TenantUserAccountCreatedMail(
                        tenant: $tenant,
                        user: $user,
                        plainPassword: $plainPassword,
                        roleLabel: User::tenantRoles()[$validated['role']] ?? $validated['role'],
                    )
                );
            } catch (Throwable $mailException) {
                report($mailException);
            }

            ActivityLogService::record(
                request: $request,
                action: 'tenant_user.create',
                description: "Created user {$user->name} in {$tenant->name}.",
                metadata: ['role' => $validated['role']],
                targetType: 'user',
                targetId: $user->id,
                tenantId: $tenant->id,
            );

            return back()->with('success', "Created {$user->name} in {$tenant->name}.");
        }

        if ($user->is_super_admin) {
            return back()->with('error', 'Super admin accounts cannot be assigned as tenant users.');
        }

        if ($this->countTenantAdmins($tenant) < 1 && $validated['role'] !== User::ROLE_BARANGAY_ADMIN) {
            return back()->with('error', 'The first tenant user must be Barangay Admin.');
        }

        $user->update(['role' => $validated['role']]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant_user.assign',
            description: "Updated {$user->name} in {$tenant->name}.",
            metadata: ['role' => $validated['role']],
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Updated {$user->name} in {$tenant->name}.");
    }

    public function createTenantUser(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        if ($this->countTenantAdmins($tenant) < 1 && $validated['role'] !== User::ROLE_BARANGAY_ADMIN) {
            return back()->with('error', 'The first tenant user must be Barangay Admin.');
        }

        $plainPassword = str()->random(16);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($plainPassword),
            'role' => $validated['role'],
            'is_super_admin' => false,
            'must_change_password' => true,
        ]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant_user.create_and_assign',
            description: "Created user {$user->name} and assigned to {$tenant->name}.",
            metadata: [
                'role' => $validated['role'],
                'credentials_emailed' => true,
            ],
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        try {
            Mail::to($user->email)->send(
                new TenantUserAccountCreatedMail(
                    tenant: $tenant,
                    user: $user,
                    plainPassword: $plainPassword,
                    roleLabel: User::tenantRoles()[$validated['role']] ?? $validated['role'],
                )
            );
        } catch (Throwable $mailException) {
            report($mailException);
        }

        return back()->with('success', "Created account for {$user->name}, assigned role, and emailed generated credentials.");
    }

    public function updateTenantUserRole(Request $request, User $user): RedirectResponse
    {
        $tenant = app('current_tenant');

        if (!$tenant) {
            return back()->with('error', 'No active tenant context was found for this request.');
        }

        if ($user->is_super_admin) {
            return back()->with('error', 'Super admin accounts cannot be updated through tenant role management.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $previousRole = $user->role;

        if (
            $previousRole === User::ROLE_BARANGAY_ADMIN
            && $validated['role'] !== User::ROLE_BARANGAY_ADMIN
            && $this->countTenantAdmins($tenant) <= 1
        ) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin.');
        }

        $user->update(['role' => $validated['role']]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant_user.role_update',
            description: "Updated role for {$user->name} in {$tenant->name}.",
            metadata: [
                'before_role' => $previousRole,
                'after_role' => $validated['role'],
            ],
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Updated role for {$user->name}.");
    }

    public function removeTenantUser(Request $request, User $user): RedirectResponse
    {
        $tenant = app('current_tenant');
        if ($user->role === User::ROLE_BARANGAY_ADMIN && $this->countTenantAdmins($tenant) <= 1) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin.');
        }

        $user->delete();

        ActivityLogService::record(
            request: $request,
            action: 'tenant_user.remove',
            description: "Removed {$user->name} from {$tenant->name}.",
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Removed {$user->name} from {$tenant->name}.");
    }

    private function countTenantAdmins(Tenant $tenant): int
    {
        return User::query()
            ->where('role', User::ROLE_BARANGAY_ADMIN)
            ->count();
    }
}