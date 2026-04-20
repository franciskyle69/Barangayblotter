<?php

namespace App\Services;

use App\Models\Tenant;
use Stripe\ApiRequestor;
use Stripe\Exception\ApiErrorException;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;
use Throwable;

class StripeTenantInvoiceService
{
    /**
     * Install a Stripe HTTP client with bounded timeouts so requests cannot stall until PHP max_execution_time.
     * Safe to call multiple times (no-op after the first).
     */
    public static function ensureStripeHttpClientConfigured(): void
    {
        static $configured = false;
        if ($configured) {
            return;
        }
        $configured = true;

        $curlOptions = [
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
        ];

        if (app()->environment('local')) {
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        ApiRequestor::setHttpClient(new CurlClient($curlOptions));
    }

    public function sendTenantCreationInvoice(Tenant $tenant, string $adminEmail, ?string $adminName = null): ?string
    {
        $tenant->loadMissing('plan');

        if (!$tenant->plan) {
            return null;
        }

        $amount = (int) round(((float) $tenant->plan->price_monthly) * 100);
        if ($amount <= 0) {
            return null;
        }

        $stripe = $this->stripeClient();
        if (!$stripe) {
            return null;
        }

        $currency = strtolower((string) config('services.stripe.currency', 'php'));

        try {
            $customer = $stripe->customers->create([
                'email' => $adminEmail,
                'name' => $adminName ?: $tenant->name,
                'metadata' => [
                    'tenant_id' => (string) $tenant->id,
                    'tenant_slug' => (string) $tenant->slug,
                    'tenant_name' => (string) $tenant->name,
                ],
            ]);

            $stripe->invoiceItems->create([
                'customer' => $customer->id,
                'currency' => $currency,
                'amount' => $amount,
                'description' => sprintf('%s plan - %s', (string) $tenant->plan->name, (string) $tenant->name),
                'metadata' => [
                    'tenant_id' => (string) $tenant->id,
                    'plan_id' => (string) $tenant->plan->id,
                ],
            ]);

            $invoice = $stripe->invoices->create([
                'customer' => $customer->id,
                'collection_method' => 'send_invoice',
                'days_until_due' => 7,
                'auto_advance' => true,
                'description' => 'Monthly subscription invoice for barangay system access.',
                'metadata' => [
                    'tenant_id' => (string) $tenant->id,
                    'tenant_slug' => (string) $tenant->slug,
                    'plan_id' => (string) $tenant->plan->id,
                ],
            ]);

            $finalized = $stripe->invoices->finalizeInvoice($invoice->id, []);
            $stripe->invoices->sendInvoice($finalized->id, []);

            return $finalized->id;
        } catch (ApiErrorException $e) {
            report($e);

            return null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function stripeClient(): ?StripeClient
    {
        $stripeSecret = (string) config('services.stripe.secret', '');
        if ($stripeSecret === '') {
            return null;
        }

        self::ensureStripeHttpClientConfigured();

        return new StripeClient([
            'api_key' => $stripeSecret,
        ]);
    }
}