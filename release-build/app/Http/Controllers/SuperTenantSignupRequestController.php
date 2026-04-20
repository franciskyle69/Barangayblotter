<?php

namespace App\Http\Controllers;

use App\Mail\TenantAdminAccountCreatedMail;
use App\Mail\TenantSignupApprovedMail;
use App\Mail\TenantSignupRejectedMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSignupRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use App\Services\StripeTenantInvoiceService;
use App\Services\TenantDatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

class SuperTenantSignupRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Super/TenantSignupRequests', [
            'plans' => Plan::query()->select('id', 'name', 'price_monthly')->orderBy('price_monthly')->get(),
            'requests' => TenantSignupRequest::query()
                ->with(['requestedPlan', 'reviewedBy', 'processedTenant'])
                ->latest()
                ->get(),
        ]);
    }

    public function approve(Request $request, TenantSignupRequest $signupRequest, TenantDatabaseManager $tenantDatabases, DatabaseBackupService $backupService, StripeTenantInvoiceService $stripeTenantInvoiceService): RedirectResponse
    {
        if ($signupRequest->status !== TenantSignupRequest::STATUS_PENDING) {
            return back()->with('warning', 'This signup request has already been processed.');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = null;
        $createdUser = null;

        try {
            $normalizedSubdomain = trim(strtolower((string) ($signupRequest->subdomain ?: $signupRequest->slug)));
            $normalizedSubdomain = $normalizedSubdomain !== '' ? $normalizedSubdomain : null;

            $normalizedCustomDomain = trim(strtolower((string) $signupRequest->custom_domain));
            $normalizedCustomDomain = $normalizedCustomDomain !== '' ? $normalizedCustomDomain : null;

            if (app()->environment('local') && $normalizedCustomDomain && !str_contains($normalizedCustomDomain, '.')) {
                $normalizedSubdomain ??= $normalizedCustomDomain;
                $normalizedCustomDomain = $normalizedCustomDomain . '.lvh.me';
            }

            if (app()->environment('local') && !$normalizedCustomDomain && $normalizedSubdomain) {
                $normalizedCustomDomain = $normalizedSubdomain . '.lvh.me';
            }

            $tenant = Tenant::create([
                'plan_id' => $validated['plan_id'],
                'name' => $signupRequest->tenant_name,
                'slug' => $signupRequest->slug,
                'subdomain' => $normalizedSubdomain,
                'custom_domain' => $normalizedCustomDomain,
                'barangay' => $signupRequest->barangay,
                'address' => $signupRequest->address,
                'contact_phone' => $signupRequest->contact_phone,
                'is_active' => true,
            ]);

            $tenantDatabases->provisionTenantDatabase($tenant);

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
                } else {
                    $adminUser->update([
                        'name' => $signupRequest->requested_admin_name,
                        'phone' => $signupRequest->requested_admin_phone,
                        'role' => User::ROLE_BARANGAY_ADMIN,
                        'must_change_password' => $adminUser->must_change_password ?: false,
                    ]);
                }

                if ($adminUser->is_super_admin) {
                    throw new \RuntimeException('Requested admin email belongs to a super admin account and cannot be assigned as tenant admin.');
                }

                return $adminUser;
            });

            $signupRequest->update([
                'status' => TenantSignupRequest::STATUS_APPROVED,
                'review_notes' => $validated['review_notes'] ?? null,
                'reviewed_by_user_id' => $request->user()->id,
                'reviewed_at' => now(),
                'processed_tenant_id' => $tenant->id,
            ]);

            $this->sendApprovedNotifications($signupRequest->fresh(), $tenant);

            if ($plainPassword) {
                try {
                    Mail::to($adminUser->email)->send(new TenantAdminAccountCreatedMail($tenant, $adminUser, $plainPassword));
                } catch (Throwable $mailException) {
                    report($mailException);
                }
            }

            ActivityLogService::record(
                request: $request,
                action: 'super.tenant_signup.approve',
                description: "Approved tenant signup request for {$signupRequest->tenant_name}.",
                metadata: [
                    'signup_request_id' => $signupRequest->id,
                    'plan_id' => $validated['plan_id'],
                ],
                targetType: 'tenant_signup_request',
                targetId: $signupRequest->id,
                tenantId: $tenant->id,
            );

            $sentInvoiceId = $stripeTenantInvoiceService->sendTenantCreationInvoice(
                tenant: $tenant,
                adminEmail: (string) $signupRequest->requested_admin_email,
                adminName: $signupRequest->requested_admin_name,
            );

            ActivityLogService::record(
                request: $request,
                action: $sentInvoiceId ? 'super.tenant_signup.invoice.sent' : 'super.tenant_signup.invoice.skipped_or_failed',
                description: $sentInvoiceId
                    ? "Sent Stripe invoice {$sentInvoiceId} after approving signup for {$signupRequest->tenant_name}."
                    : "Stripe invoice was not sent after approving signup for {$signupRequest->tenant_name}.",
                metadata: [
                    'signup_request_id' => $signupRequest->id,
                    'tenant_id' => $tenant->id,
                    'invoice_id' => $sentInvoiceId,
                    'invoice_target_email' => $signupRequest->requested_admin_email,
                ],
                targetType: 'tenant_signup_request',
                targetId: $signupRequest->id,
                tenantId: $tenant->id,
            );

            try {
                $backup = $backupService->createBackup();
                $backupFilename = $backup['filename'] ?? null;

                ActivityLogService::record(
                    request: $request,
                    action: 'super.backup.auto_after_tenant_signup_approve',
                    description: "Automatically created backup after approving signup for {$signupRequest->tenant_name}.",
                    metadata: [
                        'signup_request_id' => $signupRequest->id,
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
                    action: 'super.backup.auto_after_tenant_signup_approve_failed',
                    description: "Failed to automatically create backup after approving signup for {$signupRequest->tenant_name}.",
                    metadata: [
                        'signup_request_id' => $signupRequest->id,
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'error' => $e->getMessage(),
                    ],
                    targetType: 'backup_file',
                    tenantId: $tenant->id,
                );

                report($e);

                return back()
                    ->with('success', 'Signup request approved and tenant provisioned successfully.')
                    ->with('warning', 'Tenant was created, but automatic backup failed. Please run Backup & Restore manually.');
            }
        } catch (Throwable $e) {
            ActivityLogService::record(
                request: $request,
                action: 'super.tenant_signup.approve_failed',
                description: "Failed to approve tenant signup request for {$signupRequest->tenant_name}.",
                metadata: [
                    'signup_request_id' => $signupRequest->id,
                    'error' => $e->getMessage(),
                ],
                targetType: 'tenant_signup_request',
                targetId: $signupRequest->id,
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
            return back()->with('error', 'Failed to approve signup request: ' . $e->getMessage());
        }

        return back()->with('success', 'Signup request approved, tenant provisioned, and automatic backup generated successfully.');
    }

    public function reject(Request $request, TenantSignupRequest $signupRequest): RedirectResponse
    {
        if ($signupRequest->status !== TenantSignupRequest::STATUS_PENDING) {
            return back()->with('warning', 'This signup request has already been processed.');
        }

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Release reserved unique values so the same slug/subdomain/domain can be reused.
        $releasedSlug = $this->releasedRequestSlug($signupRequest);

        $signupRequest->update([
            'status' => TenantSignupRequest::STATUS_REJECTED,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'slug' => $releasedSlug,
            'subdomain' => null,
            'custom_domain' => null,
        ]);

        $this->sendRejectedNotifications($signupRequest->fresh());

        ActivityLogService::record(
            request: $request,
            action: 'super.tenant_signup.reject',
            description: "Rejected tenant signup request for {$signupRequest->tenant_name}.",
            metadata: [
                'signup_request_id' => $signupRequest->id,
                'released_slug' => $releasedSlug,
            ],
            targetType: 'tenant_signup_request',
            targetId: $signupRequest->id,
        );

        return back()->with('success', 'Signup request rejected.');
    }

    private function sendApprovedNotifications(TenantSignupRequest $signupRequest, Tenant $tenant): void
    {
        try {
            $emails = array_filter(array_unique([
                $signupRequest->requested_admin_email,
            ]));

            foreach ($emails as $email) {
                Mail::to($email)->send(new TenantSignupApprovedMail($signupRequest, $tenant));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function sendRejectedNotifications(TenantSignupRequest $signupRequest): void
    {
        try {
            $emails = array_filter(array_unique([
                $signupRequest->requested_admin_email,
            ]));

            foreach ($emails as $email) {
                Mail::to($email)->send(new TenantSignupRejectedMail($signupRequest));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function releasedRequestSlug(TenantSignupRequest $signupRequest): string
    {
        $suffix = '-rejected-' . $signupRequest->id;
        $maxBaseLength = 255 - strlen($suffix);
        $base = Str::limit($signupRequest->slug, $maxBaseLength, '');

        return $base . $suffix;
    }
}
