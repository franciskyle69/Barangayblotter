<?php

namespace App\Http\Controllers;

use App\Mail\TenantSignupAdminAlertMail;
use App\Mail\TenantSignupSubmittedMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TenantSignupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TenantSignupController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/TenantSignup', [
            'plans' => Plan::query()->select('id', 'name', 'price_monthly')->orderBy('price_monthly')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('tenant_signup_requests', 'slug'),
                Rule::unique('tenants', 'slug'),
            ],
            'subdomain' => [
                'nullable',
                'string',
                'max:63',
                'alpha_dash',
                Rule::unique('tenant_signup_requests', 'subdomain'),
                Rule::unique('tenants', 'subdomain'),
                Rule::notIn([config('tenancy.super_admin_subdomain', 'admin'), 'www']),
            ],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenant_signup_requests', 'custom_domain'),
                Rule::unique('tenants', 'custom_domain'),
            ],
            'barangay' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'requested_admin_name' => ['required', 'string', 'max:255'],
            'requested_admin_email' => ['required', 'email', 'max:255'],
            'requested_admin_phone' => ['nullable', 'string', 'max:50'],
            'requested_admin_role' => ['required', Rule::in([User::ROLE_PUROK_SECRETARY, User::ROLE_PUROK_LEADER])],
            'requested_admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'requested_plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        // Requested tenant admin acts as the primary contact.
        $validated['contact_name'] = $validated['requested_admin_name'];
        $validated['contact_email'] = $validated['requested_admin_email'];

        $validated['requested_admin_password_hash'] = Hash::make($validated['requested_admin_password']);
        unset($validated['requested_admin_password'], $validated['requested_admin_password_confirmation']);

        $validated['status'] = TenantSignupRequest::STATUS_PENDING;

        $signupRequest = TenantSignupRequest::create($validated);

        $this->sendSignupSubmittedNotifications($signupRequest);

        return redirect()->route('login')->with('success', 'Your tenant signup request was submitted and is pending approval by the city administrator.');
    }

    private function sendSignupSubmittedNotifications(TenantSignupRequest $signupRequest): void
    {
        try {
            // Requester acknowledgment
            Mail::to($signupRequest->requested_admin_email)
                ->send(new TenantSignupSubmittedMail($signupRequest));

            // Alert all super admins about new tenant signup requests
            $superAdminEmails = User::query()
                ->where('is_super_admin', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values();

            foreach ($superAdminEmails as $email) {
                Mail::to($email)->send(new TenantSignupAdminAlertMail($signupRequest));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
