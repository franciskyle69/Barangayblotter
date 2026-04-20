<?php

namespace App\Http\Controllers;

use App\Mail\TenantAdminAccountCreatedMail;
use App\Mail\TenantSignupApprovedMail;
use App\Mail\TenantUserAccountCreatedMail;
use App\Models\CentralIncidentSummary;
use App\Models\Incident;
use App\Models\Tenant;
use App\Models\TenantSignupRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use App\Services\StripeTenantInvoiceService;
use App\Services\TenantDatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    public function settings(): Response
    {
        return Inertia::render('Super/Settings');
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

    public function storeTenant(Request $request, TenantDatabaseManager $tenantDatabases, DatabaseBackupService $backupService, StripeTenantInvoiceService $stripeTenantInvoiceService): RedirectResponse
    {
        $this->normalizeTenantDomainInput($request, autoAssignLocalDomain: true);

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
        ]);

        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

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

            if ($signupRequest && $signupRequest->requested_admin_email) {
                $plainPassword = null;

                $adminUser = $tenantDatabases->runInTenantContext($tenant, function () use ($tenant, $signupRequest, &$plainPassword, &$createdUser) {
                    $adminUser = User::where('email', $signupRequest->requested_admin_email)->first();

                    if (!$adminUser) {
                        $plainPassword = str()->random(16);
                        $adminUser = User::create([
                            'name' => $signupRequest->requested_admin_name,
                            'email' => $signupRequest->requested_admin_email,
                            'phone' => $signupRequest->requested_admin_phone,
                            'password' => Hash::make($plainPassword),
                            'role' => User::ROLE_BARANGAY_ADMIN,
                            'is_super_admin' => false,
                            'must_change_password' => true,
                        ]);

                        $createdUser = $adminUser;
                        $createdUser->plainPassword = $plainPassword;
                    } else {
                        $adminUser->update([
                            'name' => $signupRequest->requested_admin_name,
                            'phone' => $signupRequest->requested_admin_phone,
                            'role' => User::ROLE_BARANGAY_ADMIN,
                        ]);
                    }

                    return $adminUser;
                });

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

                $adminUser = $tenantDatabases->runInTenantContext($tenant, function () use ($tenant, $requestedEmail, $requestedName, $validated, &$plainPassword, &$createdUser) {
                    $adminUser = User::where('email', $requestedEmail)->first();

                    if (!$adminUser) {
                        $plainPassword = str()->random(16);
                        $adminUser = User::create([
                            'name' => $requestedName,
                            'email' => $requestedEmail,
                            'phone' => $validated['requested_admin_phone'] ?? null,
                            'password' => Hash::make($plainPassword),
                            'role' => User::ROLE_BARANGAY_ADMIN,
                            'is_super_admin' => false,
                            'must_change_password' => true,
                        ]);

                        $createdUser = $adminUser;
                        $createdUser->plainPassword = $plainPassword;
                    } else {
                        $adminUser->update([
                            'name' => $requestedName,
                            'phone' => $validated['requested_admin_phone'] ?? null,
                            'role' => User::ROLE_BARANGAY_ADMIN,
                        ]);
                    }

                    return $adminUser;
                });

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

        $invoiceTargetEmail = $signupRequest?->requested_admin_email ?? ($validated['requested_admin_email'] ?? null);
        $invoiceTargetName = $signupRequest?->requested_admin_name ?? ($validated['requested_admin_name'] ?? null);

        if (is_string($invoiceTargetEmail) && trim($invoiceTargetEmail) !== '') {
            $sentInvoiceId = $stripeTenantInvoiceService->sendTenantCreationInvoice(
                tenant: $tenant,
                adminEmail: $invoiceTargetEmail,
                adminName: is_string($invoiceTargetName) ? $invoiceTargetName : null,
            );

            ActivityLogService::record(
                request: $request,
                action: $sentInvoiceId ? 'super.tenant.invoice.sent' : 'super.tenant.invoice.skipped_or_failed',
                description: $sentInvoiceId
                    ? "Sent Stripe invoice {$sentInvoiceId} for tenant {$tenant->name}."
                    : "Stripe invoice was not sent for tenant {$tenant->name}.",
                metadata: [
                    'tenant_id' => $tenant->id,
                    'invoice_id' => $sentInvoiceId,
                    'invoice_target_email' => $invoiceTargetEmail,
                ],
                targetType: 'tenant',
                targetId: $tenant->id,
                tenantId: $tenant->id,
            );
        }

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
        $this->normalizeTenantDomainInput($request);

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

    private function normalizeTenantDomainInput(Request $request, bool $autoAssignLocalDomain = false): void
    {
        $subdomain = $this->normalizeSubdomainValue($request->input('subdomain'));
        $customDomain = $this->normalizeHostValue($request->input('custom_domain'));

        if (app()->environment('local') && $customDomain && !str_contains($customDomain, '.')) {
            $subdomain ??= $customDomain;
            $customDomain = $customDomain . '.lvh.me';
        }

        if ($autoAssignLocalDomain && app()->environment('local') && $subdomain && !$customDomain) {
            $customDomain = $subdomain . '.lvh.me';
        }

        if (!$subdomain && $customDomain) {
            $derivedSubdomain = $this->normalizeSubdomainValue($customDomain);
            if ($derivedSubdomain && preg_match('/^[a-z0-9-]+$/', $derivedSubdomain)) {
                $subdomain = $derivedSubdomain;
            }
        }

        $request->merge([
            'subdomain' => $subdomain,
            'custom_domain' => $customDomain,
        ]);
    }

    private function normalizeSubdomainValue(mixed $value): ?string
    {
        $host = $this->normalizeHostValue($value);

        if (!$host) {
            return null;
        }

        foreach ($this->tenantBaseHosts() as $baseHost) {
            $suffix = '.' . $baseHost;
            if (str_ends_with($host, $suffix)) {
                $host = substr($host, 0, -strlen($suffix));
                break;
            }
        }

        $host = trim($host, '.');

        return $host !== '' ? $host : null;
    }

    private function normalizeHostValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        if (!str_contains($normalized, '://')) {
            $normalized = 'http://' . $normalized;
        }

        $host = parse_url($normalized, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $host = trim(strtolower($host), '.');

        return $host !== '' ? $host : null;
    }

    private function tenantBaseHosts(): array
    {
        $hosts = ['lvh.me'];

        $appHost = $this->normalizeHostValue((string) config('app.url', ''));
        if ($appHost) {
            $hosts[] = $appHost;
        }

        return array_values(array_unique($hosts));
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
        $users = app(TenantDatabaseManager::class)->runInTenantContext($tenant, function () {
            return User::query()
                ->select('id', 'name', 'email', 'phone', 'is_super_admin', 'role')
                ->orderBy('name')
                ->get();
        });

        return Inertia::render('Super/TenantUsers', [
            'tenant' => $tenant,
            'users' => $users->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => (bool) $user->is_super_admin,
                'role' => $user->role,
            ])->values(),
            'roles' => User::tenantRoles(),
        ]);
    }

    public function addTenantUser(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $result = app(TenantDatabaseManager::class)->runInTenantContext($tenant, function () use ($validated, $tenant, $request) {
            $user = User::where('email', $validated['email'])->first();

            if ($user?->is_super_admin) {
                return ['error' => 'Super admin accounts cannot be assigned as tenant users.'];
            }

            if ($this->countTenantAdmins($tenant) < 1 && $validated['role'] !== User::ROLE_BARANGAY_ADMIN) {
                return ['error' => 'Each tenant must always have at least one Barangay Admin.'];
            }

            $alreadyAssigned = (bool) $user;

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
            } else {
                $user->update(['role' => $validated['role']]);
            }

            ActivityLogService::record(
                request: $request,
                action: $alreadyAssigned ? 'super.tenant_user.role_update' : 'super.tenant_user.assign',
                description: $alreadyAssigned
                ? "Updated role for {$user->name} in {$tenant->name}."
                : "Assigned {$user->name} to {$tenant->name}.",
                metadata: ['role' => $validated['role']],
                targetType: 'user',
                targetId: $user->id,
                tenantId: $tenant->id,
            );

            return ['success' => $alreadyAssigned
                ? "Updated role for {$user->name} in {$tenant->name}."
                : "Assigned {$user->name} to {$tenant->name}."];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['success']);
    }

    public function createTenantUser(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $result = app(TenantDatabaseManager::class)->runInTenantContext($tenant, function () use ($validated, $tenant, $request) {
            if ($this->countTenantAdmins($tenant) < 1 && $validated['role'] !== User::ROLE_BARANGAY_ADMIN) {
                return ['error' => 'Each tenant must always have at least one Barangay Admin. Create the first user as Barangay Admin.'];
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
                action: 'super.tenant_user.create_and_assign',
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

            return ['success' => "Created account for {$user->name}, assigned to {$tenant->name}, and emailed generated credentials."];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['success']);
    }

    public function updateTenantUserRole(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::tenantRoles()))],
        ]);

        $previousRole = $user->role;

        if (
            $previousRole === User::ROLE_BARANGAY_ADMIN
            && $validated['role'] !== User::ROLE_BARANGAY_ADMIN
            && $this->countTenantAdmins($tenant) <= 1
        ) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin. Assign another Barangay Admin before changing this role.');
        }

        $user->update(['role' => $validated['role']]);

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant_user.role_update',
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

    public function removeTenantUser(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        if ($user->role === User::ROLE_BARANGAY_ADMIN && $this->countTenantAdmins($tenant) <= 1) {
            return back()->with('error', 'Each tenant must always have at least one Barangay Admin. Assign another Barangay Admin before removing this user.');
        }

        $user->delete();

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant_user.remove',
            description: "Removed {$user->name} from {$tenant->name}.",
            targetType: 'user',
            targetId: $user->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', "Removed {$user->name} from {$tenant->name}.");
    }

    private function countTenantAdmins(Tenant $tenant): int
    {
        return app(TenantDatabaseManager::class)->runInTenantContext($tenant, function () {
            return User::query()
                ->where('role', User::ROLE_BARANGAY_ADMIN)
            ->count();
        });
    }
}
