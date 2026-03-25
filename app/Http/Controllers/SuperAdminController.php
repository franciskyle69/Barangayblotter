<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $tenants = Tenant::with('plan')->withCount('incidents')->get();
        // Super admin sees ALL incidents across tenants (bypass global scope)
        $totalIncidents = Incident::withoutGlobalScope('tenant')->count();
        $incidentsThisMonth = Incident::withoutGlobalScope('tenant')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $byStatus = Incident::withoutGlobalScope('tenant')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $recentIncidents = Incident::withoutGlobalScope('tenant')
            ->with(['tenant', 'reportedBy'])
            ->latest()
            ->limit(20)
            ->get();
        return Inertia::render('Super/Dashboard', [
            'tenants' => $tenants,
            'totalIncidents' => $totalIncidents,
            'incidentsThisMonth' => $incidentsThisMonth,
            'byStatus' => $byStatus,
            'recentIncidents' => $recentIncidents,
        ]);
    }

    public function tenants(): Response
    {
        $tenants = Tenant::with('plan')->withCount('incidents')->get();
        return Inertia::render('Super/Tenants', ['tenants' => $tenants]);
    }

    public function createTenant(): Response
    {
        $plans = \App\Models\Plan::all();
        return Inertia::render('Super/TenantForm', ['plans' => $plans]);
    }

    public function storeTenant(Request $request): RedirectResponse
    {
        $reservedSubdomain = config('tenancy.super_admin_subdomain', 'admin');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug|alpha_dash',
            'subdomain' => [
                'nullable',
                'string',
                'max:63',
                'alpha_dash',
                'unique:tenants,subdomain',
                Rule::notIn([$reservedSubdomain, 'www']),
            ],
            'custom_domain' => 'nullable|string|max:255|unique:tenants,custom_domain',
            'barangay' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'contact_phone' => 'nullable|string|max:50',
            'plan_id' => 'required|exists:plans,id',
            'is_active' => 'boolean',
        ]);

        Tenant::create($validated);

        return redirect()->route('super.tenants')->with('success', 'Barangay tenant created successfully.');
    }

    public function editTenant(Tenant $tenant): Response
    {
        $plans = \App\Models\Plan::all();
        return Inertia::render('Super/TenantForm', [
            'tenant' => $tenant->load('plan'),
            'plans' => $plans,
        ]);
    }

    public function updateTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        $reservedSubdomain = config('tenancy.super_admin_subdomain', 'admin');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'subdomain' => [
                'nullable',
                'string',
                'max:63',
                'alpha_dash',
                Rule::unique('tenants', 'subdomain')->ignore($tenant->id),
                Rule::notIn([$reservedSubdomain, 'www']),
            ],
            'custom_domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'custom_domain')->ignore($tenant->id)],
            'barangay' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'contact_phone' => 'nullable|string|max:50',
            'plan_id' => 'required|exists:plans,id',
            'is_active' => 'boolean',
        ]);

        $tenant->update($validated);

        return redirect()->route('super.tenants')->with('success', 'Barangay tenant updated successfully.');
    }

    public function toggleActive(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        $status = $tenant->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Barangay {$tenant->name} has been {$status}.");
    }

    public function deleteTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        // Validate confirmation
        $validated = $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        if ($validated['confirmation'] !== $tenant->name) {
            return back()->withErrors(['confirmation' => 'Barangay name does not match.']);
        }

        $tenantName = $tenant->name;

        try {
            // Delete all related data in a transaction
            \Illuminate\Support\Facades\DB::transaction(function () use ($tenant) {
                // Delete related data (order matters for foreign keys)
                $tenant->incidents()->delete();
                $tenant->mediations()->delete();
                $tenant->patrolLogs()->delete();
                $tenant->blotterRequests()->delete();

                // Detach users
                $tenant->users()->detach();

                // Delete the tenant
                $tenant->delete();
            });

            return redirect()->route('super.tenants')->with('success', "Barangay '{$tenantName}' and all its data have been permanently deleted.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete barangay: ' . $e->getMessage());
        }
    }

    public function tenantUsers(Tenant $tenant): Response
    {
        $tenant->load([
            'users' => fn($query) => $query->select('users.id', 'name', 'email', 'is_super_admin')->orderBy('name'),
            'plan',
        ]);

        return Inertia::render('Super/TenantUsers', [
            'tenant' => $tenant,
            'users' => $tenant->users->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => (bool) $user->is_super_admin,
                'role' => $user->pivot?->role,
            ])->values(),
            'roles' => User::tenantRoles(),
        ]);
    }

    public function addTenantUser(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if ($user->is_super_admin) {
            return back()->with('error', 'Super admin accounts cannot be assigned as tenant users.');
        }

        $alreadyAssigned = $tenant->users()->where('users.id', $user->id)->exists();

        $tenant->users()->syncWithoutDetaching([
            $user->id => ['role' => $validated['role']],
        ]);

        if ($alreadyAssigned) {
            return back()->with('success', "Updated role for {$user->name} in {$tenant->name}.");
        }

        return back()->with('success', "Assigned {$user->name} to {$tenant->name}.");
    }

    public function createTenantUser(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_super_admin' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => $validated['role']]);

        return back()->with('success', "Created account for {$user->name} and assigned to {$tenant->name}.");
    }

    public function updateTenantUserRole(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $belongs = $tenant->users()->where('users.id', $user->id)->exists();
        if (!$belongs) {
            return back()->with('error', 'That user is not assigned to this barangay.');
        }

        $tenant->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', "Updated role for {$user->name}.");
    }

    public function removeTenantUser(Tenant $tenant, User $user): RedirectResponse
    {
        $belongs = $tenant->users()->where('users.id', $user->id)->exists();
        if (!$belongs) {
            return back()->with('warning', 'That user is already not assigned to this barangay.');
        }

        $tenant->users()->detach($user->id);

        return back()->with('success', "Removed {$user->name} from {$tenant->name}.");
    }
}
