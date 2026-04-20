<?php

namespace App\Http\Controllers;

use App\Mail\TenantSignupAdminAlertMail;
use App\Mail\TenantSignupSubmittedMail;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSignupRequest;
use App\Models\User;
use App\Services\StripeTenantInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TenantSignupController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/TenantSignup', [
            'plans' => Plan::query()->select('id', 'name', 'price_monthly')->orderBy('price_monthly')->get(),
        ]);
    }

    public function store(Request $request): HttpResponse
    {
        $this->normalizeTenantDomainInput($request, autoAssignLocalDomain: true);

        $validated = $request->validate($this->signupValidationRules());

        // Requested tenant admin acts as the primary contact.
        $validated['contact_name'] = $validated['requested_admin_name'];
        $validated['contact_email'] = $validated['requested_admin_email'];
        $validated['requested_admin_role'] = User::ROLE_BARANGAY_ADMIN;

        $plan = null;
        if (!empty($validated['requested_plan_id'])) {
            $plan = Plan::query()->find($validated['requested_plan_id']);
        }

        if ($this->requiresStripeCheckout($plan)) {
            $checkoutUrl = $this->createStripeCheckoutUrl($validated, $plan);

            if (!$checkoutUrl) {
                return back()->with('error', 'Unable to start Stripe checkout. Please try again later.')
                    ->withInput();
            }

            $request->session()->put('tenant_signup_draft', $validated);

            return Inertia::location($checkoutUrl);
        }

        $this->persistSignupRequest($validated);

        return redirect()->route('login')->with('success', 'Your tenant signup request was submitted and is pending approval by the city administrator.');
    }

    public function paymentSuccess(Request $request): RedirectResponse
    {
        $sessionId = trim((string) $request->query('session_id', ''));
        $draft = $request->session()->get('tenant_signup_draft');

        if ($sessionId === '' || !is_array($draft)) {
            return redirect()->route('tenant-signup.create')
                ->with('error', 'Payment verification failed because your signup draft is missing. Please submit again.');
        }

        if (!$this->isStripePaymentSuccessful($sessionId)) {
            return redirect()->route('tenant-signup.create')
                ->with('error', 'Payment could not be verified. Please contact support if you were charged.');
        }

        if (!$this->isSignupDraftStillAvailable($draft)) {
            $request->session()->forget('tenant_signup_draft');

            return redirect()->route('tenant-signup.create')
                ->with('error', 'The tenant slug or domain is no longer available. Please submit a new request.');
        }

        $request->session()->forget('tenant_signup_draft');
        $this->persistSignupRequest($draft);

        return redirect()->route('login')->with('success', 'Payment received. Your tenant signup request was submitted and is pending approval by the city administrator.');
    }

    public function paymentCancel(): RedirectResponse
    {
        return redirect()->route('tenant-signup.create')
            ->with('warning', 'Stripe checkout was cancelled. Your tenant signup request was not submitted.');
    }

    private function signupValidationRules(): array
    {
        return [
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
            'requested_plan_id' => ['nullable', 'exists:plans,id'],
        ];
    }

    private function persistSignupRequest(array $payload): TenantSignupRequest
    {
        $payload['status'] = TenantSignupRequest::STATUS_PENDING;

        $signupRequest = TenantSignupRequest::create($payload);

        $this->sendSignupSubmittedNotifications($signupRequest);

        return $signupRequest;
    }

    private function requiresStripeCheckout(?Plan $plan): bool
    {
        if (!$plan) {
            return false;
        }

        return (float) $plan->price_monthly > 0;
    }

    private function createStripeCheckoutUrl(array $payload, Plan $plan): ?string
    {
        $stripe = $this->stripeClient();
        if (!$stripe) {
            return null;
        }

        $currency = strtolower((string) config('services.stripe.currency', 'php'));
        $amount = (int) round(((float) $plan->price_monthly) * 100);

        if ($amount <= 0) {
            return null;
        }

        try {
            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'success_url' => route('tenant-signup.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('tenant-signup.payment.cancel'),
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $amount,
                        'product_data' => [
                            'name' => $plan->name . ' Plan',
                            'description' => 'Tenant signup for ' . $payload['tenant_name'],
                        ],
                    ],
                ]],
                'metadata' => [
                    'tenant_slug' => $payload['slug'],
                    'requested_admin_email' => $payload['requested_admin_email'],
                ],
            ]);

            return is_string($session->url) && $session->url !== '' ? $session->url : null;
        } catch (ApiErrorException $e) {
            report($e);

            return null;
        }
    }

    private function isStripePaymentSuccessful(string $checkoutSessionId): bool
    {
        $stripe = $this->stripeClient();
        if (!$stripe) {
            return false;
        }

        try {
            $session = $stripe->checkout->sessions->retrieve($checkoutSessionId, []);

            return $session->payment_status === 'paid';
        } catch (ApiErrorException $e) {
            report($e);

            return false;
        }
    }

    private function stripeClient(): ?StripeClient
    {
        $stripeSecret = (string) config('services.stripe.secret', '');
        if ($stripeSecret === '') {
            return null;
        }

        $clientOptions = [
            'api_key' => $stripeSecret,
        ];

        StripeTenantInvoiceService::ensureStripeHttpClientConfigured();

        return new StripeClient($clientOptions);
    }

    private function isSignupDraftStillAvailable(array $draft): bool
    {
        $slug = (string) ($draft['slug'] ?? '');
        if ($slug === '') {
            return false;
        }

        if (TenantSignupRequest::query()->where('slug', $slug)->exists()) {
            return false;
        }

        if (Tenant::query()->where('slug', $slug)->exists()) {
            return false;
        }

        $subdomain = $draft['subdomain'] ?? null;
        if (is_string($subdomain) && $subdomain !== '') {
            if (TenantSignupRequest::query()->where('subdomain', $subdomain)->exists()) {
                return false;
            }

            if (Tenant::query()->where('subdomain', $subdomain)->exists()) {
                return false;
            }
        }

        $customDomain = $draft['custom_domain'] ?? null;
        if (is_string($customDomain) && $customDomain !== '') {
            if (TenantSignupRequest::query()->where('custom_domain', $customDomain)->exists()) {
                return false;
            }

            if (Tenant::query()->where('custom_domain', $customDomain)->exists()) {
                return false;
            }
        }

        return true;
    }

    private function sendSignupSubmittedNotifications(TenantSignupRequest $signupRequest): void
    {
        try {
            Mail::to($signupRequest->requested_admin_email)
                ->send(new TenantSignupSubmittedMail($signupRequest));

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
        } catch (\Throwable $e) {
            report($e);
        }
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
}
