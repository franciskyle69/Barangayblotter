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
use Illuminate\Support\Facades\Storage;
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
                'requested_admin_role' => User::ROLE_BARANGAY_ADMIN,
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
            'requested_admin_name' => 'required_without:signup_request_id|string|max:255',
            'requested_admin_email' => 'required_without:signup_request_id|email|max:255',
            'requested_admin_phone' => 'nullable|string|max:50',
            'sidebar_label' => 'nullable|string|max:100',
            'logo_choice' => ['required', 'string', Rule::in(['default', 'blue', 'green', 'amber', 'custom'])],
            'logo_file' => ['required_if:logo_choice,custom', 'nullable', 'image', 'max:2048'],
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
                'sidebar_label',
                'barangay',
                'address',
                'contact_phone',
                'plan_id',
                'is_active',
            ]);

            $tenantPayload['sidebar_label'] = trim((string) ($tenantPayload['sidebar_label'] ?? '')) ?: null;

            $tenant = Tenant::create($tenantPayload);
            $tenantDatabases->provisionTenantDatabase($tenant);

            if ($validated['logo_choice'] === 'custom') {
                if ($request->hasFile('logo_file')) {
                    $tenant->logo_path = $request->file('logo_file')->store('tenant-branding/' . $tenant->id, 'public');
                    $tenant->save();
                } else {
                    throw new \RuntimeException('A custom logo file is required when selecting the custom logo option.');
                }
            } else {
                $logoPath = $this->logoChoiceToPath($validated['logo_choice']);
                if ($logoPath) {
                    $tenant->logo_path = $logoPath;
                    $tenant->save();
                }
            }

            if ($signupRequest && $signupRequest->requested_admin_email) {
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
                    $adminUser->id => ['role' => User::ROLE_BARANGAY_ADMIN],
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
            } elseif (!empty($validated['requested_admin_email'])) {
                $requestedEmail = strtolower(trim((string) $validated['requested_admin_email']));
                $requestedName = trim((string) ($validated['requested_admin_name'] ?? ''));
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
                    $adminUser->id => ['role' => User::ROLE_BARANGAY_ADMIN],
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
            } else {
                throw new \RuntimeException('Tenant creation requires a Barangay Admin account.');
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
                    if ($tenant->logo_path) {
                        Storage::disk('public')->delete($tenant->logo_path);
                    }
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
        $tenant = $tenant->load('plan');

        return Inertia::render('Super/TenantForm', [
            'tenant' => array_merge($tenant->toArray(), [
                'logo_url' => $tenant->logo_url,
                'logo_choice' => $this->resolveLogoChoice($tenant->getRawOriginal('logo_path')),
            ]),
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
            'sidebar_label' => ['nullable', 'string', 'max:100'],
            'logo_choice' => ['required', 'string', Rule::in(['default', 'blue', 'green', 'amber', 'custom'])],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'barangay' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'contact_phone' => 'nullable|string|max:50',
            'plan_id' => 'required|exists:plans,id',
            'is_active' => 'boolean',
        ]);

        $currentLogoPath = $tenant->getRawOriginal('logo_path');
        if ($validated['logo_choice'] === 'custom') {
            if ($request->hasFile('logo_file')) {
                if ($currentLogoPath && !str_starts_with($currentLogoPath, 'images/')) {
                    Storage::disk('public')->delete($currentLogoPath);
                }

                $validated['logo_path'] = $request->file('logo_file')->store('tenant-branding/' . $tenant->id, 'public');
            } elseif ($currentLogoPath && !str_starts_with((string) $currentLogoPath, 'images/')) {
                $validated['logo_path'] = $currentLogoPath;
            } else {
                return back()->withErrors(['logo_file' => 'Please upload a custom logo file.']);
            }
        } else {
            if ($currentLogoPath && !str_starts_with($currentLogoPath, 'images/')) {
                Storage::disk('public')->delete($currentLogoPath);
            }

            $validated['logo_path'] = $this->logoChoiceToPath($validated['logo_choice']);
        }

        if (array_key_exists('sidebar_label', $validated)) {
            $validated['sidebar_label'] = trim((string) $validated['sidebar_label']) ?: null;
        }

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
                    'sidebar_label' => $tenant->sidebar_label,
                    'logo_path' => $tenant->logo_path,
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

    private function logoChoiceToPath(string $choice): ?string
    {
        return match ($choice) {
            'blue' => 'images/logo-blue.svg',
            'green' => 'images/logo-green.svg',
            'amber' => 'images/logo-amber.svg',
            default => null,
        };
    }

    private function resolveLogoChoice(?string $logoPath): string
    {
        return match ($logoPath) {
            'images/logo-blue.svg' => 'blue',
            'images/logo-green.svg' => 'green',
            'images/logo-amber.svg' => 'amber',
            null, '' => 'default',
            default => 'custom',
        };
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

        if ($this->countTenantAdmins($tenant) < 1 && $validated['role'] !== User::ROLE_BARANGAY_ADMIN) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin. Assign Barangay Admin first.');
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

        if ($this->countTenantAdmins($tenant) < 1 && $validated['role'] !== User::ROLE_BARANGAY_ADMIN) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin. Create the first user as Barangay Admin.');
        }

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

        if (
            $previousRole === User::ROLE_BARANGAY_ADMIN
            && $validated['role'] !== User::ROLE_BARANGAY_ADMIN
            && $this->countTenantAdmins($tenant) <= 1
        ) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin. Assign another Barangay Admin before changing this role.');
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
        $pivot = $tenant->users()->where('users.id', $user->id)->first()?->pivot;
        $belongs = (bool) $pivot;
        if (!$belongs) {
            return back()->with('warning', 'That user is already not assigned to this barangay.');
        }

        if ($pivot?->role === User::ROLE_BARANGAY_ADMIN && $this->countTenantAdmins($tenant) <= 1) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin. Assign another Barangay Admin before removing this user.');
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

    private function countTenantAdmins(Tenant $tenant): int
    {
        return $tenant->users()
            ->wherePivot('role', User::ROLE_BARANGAY_ADMIN)
            ->count();
    }
}
