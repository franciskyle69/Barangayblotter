<?php

namespace App\Http\Controllers;

use App\Mail\TenantAdminAccountCreatedMail;
use App\Mail\TenantSignupApprovedMail;
use App\Models\CentralIncidentSummary;
use App\Models\Incident;
use App\Models\Tenant;
use App\Models\TenantSignupRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use App\Services\TenantDatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SuperAdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $tenantCounts = CentralIncidentSummary::query()
            ->selectRaw('tenant_id, count(*) as total')
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $tenants = Tenant::with('plan')->get()->map(function (Tenant $tenant) use ($tenantCounts) {
            $tenant->incidents_count = (int) ($tenantCounts[$tenant->id] ?? 0);
            return $tenant;
        })->values();

        $totalIncidents = CentralIncidentSummary::query()->count();
        $incidentsThisMonth = CentralIncidentSummary::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $byStatus = CentralIncidentSummary::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentIncidents = CentralIncidentSummary::query()
            ->latest('created_at_in_tenant')
            ->limit(20)
            ->get()
            ->map(function (CentralIncidentSummary $summary) {
                return [
                    'id' => $summary->id,
                    'blotter_number' => $summary->blotter_number,
                    'incident_type' => $summary->incident_type,
                    'status' => $summary->status,
                    'created_at' => $summary->created_at_in_tenant,
                    'tenant' => [
                        'id' => $summary->tenant_id,
                        'name' => $summary->tenant_name,
                    ],
                    'reportedBy' => [
                        'id' => $summary->reported_by_user_id,
                        'name' => $summary->reported_by_name,
                    ],
                ];
            })
            ->values();

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

    public function createTenant(Request $request): Response
    {
        $plans = \App\Models\Plan::all();

        $signupRequests = TenantSignupRequest::query()
            ->where('status', TenantSignupRequest::STATUS_PENDING)
            ->with('requestedPlan:id,name')
            ->latest()
            ->get()
            ->map(fn(TenantSignupRequest $request) => [
                'id' => $request->id,
                'tenant_name' => $request->tenant_name,
                'slug' => $request->slug,
                'subdomain' => $request->subdomain,
                'custom_domain' => $request->custom_domain,
                'barangay' => $request->barangay,
                'address' => $request->address,
                'contact_phone' => $request->contact_phone,
                'requested_plan_id' => $request->requested_plan_id,
                'requested_plan_name' => $request->requestedPlan?->name,
                'requested_admin_name' => $request->requested_admin_name,
                'requested_admin_email' => $request->requested_admin_email,
                'requested_admin_phone' => $request->requested_admin_phone,
                'requested_admin_role' => $request->requested_admin_role,
            ])
            ->values();

        return Inertia::render('Super/TenantForm', [
            'plans' => $plans,
            'signupRequests' => $signupRequests,
            'initialSignupRequestId' => $request->query('signup_request_id'),
        ]);
    }

    public function storeTenant(Request $request, TenantDatabaseManager $tenantDatabases, DatabaseBackupService $backupService): RedirectResponse
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
            'signup_request_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant_signup_requests', 'id')->where(fn($query) => $query->where('status', TenantSignupRequest::STATUS_PENDING)),
            ],
            'use_requested_admin_account' => 'nullable|boolean',
            'requested_admin_name' => 'nullable|string|max:255',
            'requested_admin_email' => 'nullable|email|max:255',
            'requested_admin_phone' => 'nullable|string|max:50',
            'requested_admin_role' => ['nullable', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $tenant = null;
        $createdUser = null;
        $adminAssigned = false;
        $adminAssignmentSource = null;

        $signupRequest = null;
        if (!empty($validated['signup_request_id'])) {
            $signupRequest = TenantSignupRequest::query()
                ->where('id', $validated['signup_request_id'])
                ->where('status', TenantSignupRequest::STATUS_PENDING)
                ->first();
        }

        try {
            $tenantPayload = Arr::only($validated, [
                'name',
                'slug',
                'subdomain',
                'custom_domain',
                'barangay',
                'address',
                'contact_phone',
                'plan_id',
                'is_active',
            ]);

            $tenant = Tenant::create($tenantPayload);
            $tenantDatabases->provisionTenantDatabase($tenant);

            $shouldUseRequestedAdmin = (bool) ($validated['use_requested_admin_account'] ?? true);
            if ($signupRequest && $shouldUseRequestedAdmin && $signupRequest->requested_admin_email) {
                $adminUser = User::where('email', $signupRequest->requested_admin_email)->first();
                $plainPassword = null;

                if (!$adminUser) {
                    $plainPassword = str()->random(16);
                    $adminUser = User::create([
                        'name' => $signupRequest->requested_admin_name,
                        'email' => $signupRequest->requested_admin_email,
                        'phone' => $signupRequest->requested_admin_phone,
                        'password' => Hash::make($plainPassword),
                        'is_super_admin' => false,
                    ]);

                    $createdUser = $adminUser;
                    $createdUser->plainPassword = $plainPassword;
                }

                if ($adminUser->is_super_admin) {
                    throw new \RuntimeException('Requested admin email belongs to a super admin account and cannot be assigned as tenant admin.');
                }

                $tenant->users()->syncWithoutDetaching([
                    $adminUser->id => ['role' => $signupRequest->requested_admin_role ?: User::ROLE_PUROK_SECRETARY],
                ]);

                $adminAssigned = true;
                $adminAssignmentSource = 'signup_request';

                $signupRequest->update([
                    'status' => TenantSignupRequest::STATUS_APPROVED,
                    'review_notes' => trim((string) ($signupRequest->review_notes ?: '')),
                    'reviewed_by_user_id' => $request->user()?->id,
                    'reviewed_at' => now(),
                    'processed_tenant_id' => $tenant->id,
                ]);

                try {
                    Mail::to($signupRequest->requested_admin_email)->send(new TenantSignupApprovedMail($signupRequest->fresh(), $tenant));
                    
                    if ($plainPassword) {
                        Mail::to($adminUser->email)->send(new TenantAdminAccountCreatedMail($tenant, $adminUser, $plainPassword));
                    }
                } catch (Throwable $mailException) {
                    report($mailException);
                }
            } elseif ($shouldUseRequestedAdmin && !empty($validated['requested_admin_email'])) {
                $requestedEmail = strtolower(trim((string) $validated['requested_admin_email']));
                $requestedName = trim((string) ($validated['requested_admin_name'] ?? ''));
                $requestedRole = $validated['requested_admin_role'] ?? User::ROLE_PUROK_SECRETARY;
                $plainPassword = null;

                if ($requestedName === '') {
                    throw new \RuntimeException('Requested tenant admin name is required when assigning an admin account.');
                }

                $adminUser = User::where('email', $requestedEmail)->first();

                if (!$adminUser) {
                    $plainPassword = str()->random(16);
                    $adminUser = User::create([
                        'name' => $requestedName,
                        'email' => $requestedEmail,
                        'phone' => $validated['requested_admin_phone'] ?? null,
                        'password' => Hash::make($plainPassword),
                        'is_super_admin' => false,
                    ]);

                    $createdUser = $adminUser;
                    $createdUser->plainPassword = $plainPassword;
                }

                if ($adminUser->is_super_admin) {
                    throw new \RuntimeException('Requested admin email belongs to a super admin account and cannot be assigned as tenant admin.');
                }

                $tenant->users()->syncWithoutDetaching([
                    $adminUser->id => ['role' => $requestedRole],
                ]);

                $adminAssigned = true;
                $adminAssignmentSource = 'manual_form';

                if ($plainPassword) {
                    try {
                        Mail::to($adminUser->email)->send(new TenantAdminAccountCreatedMail($tenant, $adminUser, $plainPassword));
                    } catch (Throwable $mailException) {
                        report($mailException);
                    }
                }
            }
        } catch (Throwable $e) {
            ActivityLogService::record(
                request: $request,
                action: 'super.tenant.create_failed',
                description: 'Failed to create barangay tenant.',
                metadata: [
                    'name' => $validated['name'] ?? null,
                    'slug' => $validated['slug'] ?? null,
                    'error' => $e->getMessage(),
                ],
                targetType: 'tenant',
            );

            if ($tenant?->exists) {
                try {
                    $tenant->delete();
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            if ($createdUser?->exists) {
                try {
                    $createdUser->delete();
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            report($e);

            return back()
                ->withInput()
                ->with('error', 'Failed to create barangay tenant. Database provisioning failed: ' . $e->getMessage());
        }

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant.create',
            description: "Created barangay tenant {$tenant->name}.",
            metadata: [
                'slug' => $tenant->slug,
                'plan_id' => $tenant->plan_id,
                'subdomain' => $tenant->subdomain,
                'custom_domain' => $tenant->custom_domain,
                'signup_request_id' => $signupRequest?->id,
                'requested_admin_assigned' => $adminAssigned,
                'requested_admin_assignment_source' => $adminAssignmentSource,
                'requested_admin_email' => $signupRequest?->requested_admin_email ?? ($validated['requested_admin_email'] ?? null),
            ],
            targetType: 'tenant',
            targetId: $tenant->id,
            tenantId: $tenant->id,
        );

        $backupFilename = null;

        try {
            $backup = $backupService->createBackup();
            $backupFilename = $backup['filename'] ?? null;

            ActivityLogService::record(
                request: $request,
                action: 'super.backup.auto_after_tenant_create',
                description: "Automatically created backup after tenant {$tenant->name} was created.",
                metadata: [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'filename' => $backupFilename,
                ],
                targetType: 'backup_file',
                targetId: $backupFilename,
                tenantId: $tenant->id,
            );
        } catch (Throwable $e) {
            ActivityLogService::record(
                request: $request,
                action: 'super.backup.auto_after_tenant_create_failed',
                description: "Failed to automatically create backup after tenant {$tenant->name} was created.",
                metadata: [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'error' => $e->getMessage(),
                ],
                targetType: 'backup_file',
                tenantId: $tenant->id,
            );

            report($e);

            return redirect()->route('super.tenants')
                ->with('success', 'Barangay tenant created successfully.')
                ->with('warning', 'Tenant created, but automatic backup failed. Please run Backup & Restore manually.');
        }

        $successMessage = 'Barangay tenant created successfully.';

        if (is_string($backupFilename) && $backupFilename !== '') {
            $successMessage .= " Automatic backup created: {$backupFilename}.";
        }

        return redirect()->route('super.tenants')->with('success', $successMessage);
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
        $before = [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'subdomain' => $tenant->subdomain,
            'custom_domain' => $tenant->custom_domain,
            'plan_id' => $tenant->plan_id,
            'is_active' => $tenant->is_active,
        ];

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

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant.update',
            description: "Updated barangay tenant {$tenant->name}.",
            metadata: [
                'before' => $before,
                'after' => [
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'subdomain' => $tenant->subdomain,
                    'custom_domain' => $tenant->custom_domain,
                    'plan_id' => $tenant->plan_id,
                    'is_active' => $tenant->is_active,
                ],
            ],
            targetType: 'tenant',
            targetId: $tenant->id,
            tenantId: $tenant->id,
        );

        return redirect()->route('super.tenants')->with('success', 'Barangay tenant updated successfully.');
    }

    public function toggleActive(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        $status = $tenant->is_active ? 'activated' : 'deactivated';

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant.toggle_active',
            description: "{$status} barangay tenant {$tenant->name}.",
            metadata: ['is_active' => $tenant->is_active],
            targetType: 'tenant',
            targetId: $tenant->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Barangay {$tenant->name} has been {$status}.");
    }

    public function deleteTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        // Some clients may not send body payload for DELETE reliably.
        // If confirmation is provided, enforce exact (trimmed) name match.
        $confirmation = $request->input('confirmation');
        if (is_string($confirmation) && trim($confirmation) !== '') {
            if (trim($confirmation) !== trim($tenant->name)) {
                return back()->withErrors(['confirmation' => 'Barangay name does not match.']);
            }
        }

        $tenantName = $tenant->name;
        $tenantId = $tenant->id;

        try {
            // Delete all related data in a transaction
            \Illuminate\Support\Facades\DB::transaction(function () use ($tenant) {
                // Delete related data (order matters for foreign keys)
                $tenant->incidents()->delete();
                $tenant->mediations()->delete();
                $tenant->patrolLogs()->delete();
                $tenant->blotterRequests()->delete();
                CentralIncidentSummary::query()->where('tenant_id', $tenant->id)->delete();

                // Detach users
                $tenant->users()->detach();

                // Delete the tenant
                $tenant->delete();
            });

            ActivityLogService::record(
                request: $request,
                action: 'super.tenant.delete',
                description: "Deleted barangay tenant {$tenantName}.",
                metadata: ['tenant_name' => $tenantName],
                targetType: 'tenant',
                targetId: $tenantId,
                tenantId: $tenantId,
            );

            return redirect()->route('super.tenants')->with('success', "Barangay '{$tenantName}' and all its data have been permanently deleted.");
        } catch (\Exception $e) {
            ActivityLogService::record(
                request: $request,
                action: 'super.tenant.delete_failed',
                description: "Failed to delete barangay tenant {$tenantName}.",
                metadata: [
                    'tenant_name' => $tenantName,
                    'error' => $e->getMessage(),
                ],
                targetType: 'tenant',
                targetId: $tenantId,
                tenantId: $tenantId,
            );

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

        ActivityLogService::record(
            request: $request,
            action: $alreadyAssigned ? 'super.tenant_user.role_update' : 'super.tenant_user.assign',
            description: $alreadyAssigned
            ? "Updated role for {$user->name} in {$tenant->name}."
            : "Assigned {$user->name} to {$tenant->name}.",
            metadata: ['role' => $validated['role']],
            targetType: 'tenant_user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

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

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant_user.create_and_assign',
            description: "Created user {$user->name} and assigned to {$tenant->name}.",
            metadata: ['role' => $validated['role']],
            targetType: 'tenant_user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Created account for {$user->name} and assigned to {$tenant->name}.");
    }

    public function updateTenantUserRole(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        $previousRole = $tenant->users()->where('users.id', $user->id)->first()?->pivot?->role;

        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $belongs = $tenant->users()->where('users.id', $user->id)->exists();
        if (!$belongs) {
            return back()->with('error', 'That user is not assigned to this barangay.');
        }

        $tenant->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant_user.role_update',
            description: "Updated role for {$user->name} in {$tenant->name}.",
            metadata: [
                'before_role' => $previousRole,
                'after_role' => $validated['role'],
            ],
            targetType: 'tenant_user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Updated role for {$user->name}.");
    }

    public function removeTenantUser(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        $belongs = $tenant->users()->where('users.id', $user->id)->exists();
        if (!$belongs) {
            return back()->with('warning', 'That user is already not assigned to this barangay.');
        }

        $tenant->users()->detach($user->id);

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant_user.remove',
            description: "Removed {$user->name} from {$tenant->name}.",
            targetType: 'tenant_user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Removed {$user->name} from {$tenant->name}.");
    }
}
