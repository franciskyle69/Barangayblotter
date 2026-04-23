<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyTenantSessionBinding;
use App\Mail\IncidentReportedToOfficialsMail;
use App\Models\Incident;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Locks in the security and correctness invariants that protect this app's
 * multitenancy boundary. Every test here represents a bug we've already
 * fixed or a guarantee we can't afford to regress:
 *
 *   - BelongsToTenant scope must DENY by default (no context → no rows)
 *   - BelongsToTenant creating hook must REFUSE orphan inserts
 *   - VerifyTenantSessionBinding must reject replayed sessions
 *   - IncidentReportedToOfficialsMail must be queue-safe (no Eloquent models)
 *
 * Breaking any of these opens a cross-tenant data leak or session replay.
 * Do not loosen these tests without a design review.
 */
class MultitenancySecurityTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;
    private Tenant $tenantA;
    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::query()->create([
            'name' => 'Security Test Plan',
            'slug' => 'security-test-' . Str::lower(Str::random(8)),
            'incident_limit_per_month' => null,
            'online_complaint_submission' => true,
            'mediation_scheduling' => true,
            'sms_status_updates' => false,
            'analytics_dashboard' => true,
            'auto_case_number' => false,
            'qr_verification' => false,
            'central_monitoring' => true,
            'price_monthly' => 0,
        ]);

        $this->tenantA = Tenant::factory()->create([
            'name' => 'Tenant A',
            'plan_id' => $this->plan->id,
        ]);
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Tenant B',
            'plan_id' => $this->plan->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // BelongsToTenant global scope: deny-by-default
    // ---------------------------------------------------------------------

    public function test_incident_queries_return_nothing_when_no_tenant_context_is_bound(): void
    {
        Incident::factory()->create(['tenant_id' => $this->tenantA->id]);
        Incident::factory()->create(['tenant_id' => $this->tenantB->id]);

        // Deliberately no `app()->instance('current_tenant', ...)` and no
        // `session(['current_tenant_id' => ...])`. Queries must fail safe.
        $this->flushTenantContext();

        $this->assertSame(
            0,
            Incident::query()->count(),
            'Tenant-scoped query without context must return no rows — otherwise a stray controller or console command can leak cross-tenant data.',
        );
    }

    public function test_incident_queries_can_still_be_scoped_explicitly_without_context(): void
    {
        Incident::factory()->create(['tenant_id' => $this->tenantA->id]);
        Incident::factory()->create(['tenant_id' => $this->tenantB->id]);

        $this->flushTenantContext();

        // The escape hatch must continue to work for super-admin tooling.
        $this->assertSame(2, Incident::withoutGlobalScope('tenant')->count());
    }

    // ---------------------------------------------------------------------
    // BelongsToTenant creating hook: refuse orphan inserts
    // ---------------------------------------------------------------------

    public function test_creating_an_incident_without_tenant_context_throws(): void
    {
        $this->flushTenantContext();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/without a tenant context/i');

        Incident::query()->create([
            'incident_type' => 'Noise Complaint',
            'description' => 'Should never be inserted.',
            'location' => 'Orphan Row',
            'incident_date' => now(),
            'complainant_name' => 'A',
            'respondent_name' => 'B',
            'status' => Incident::STATUS_OPEN,
            'submitted_online' => false,
        ]);
    }

    public function test_creating_an_incident_with_explicit_tenant_id_bypasses_the_guard(): void
    {
        // Explicit tenant_id is always honored — this is the supported path
        // for super-admin seeders and backfill jobs that know exactly which
        // tenant they're writing to.
        Session::put('current_tenant_id', $this->tenantA->id);

        $incident = Incident::query()->create([
            'tenant_id' => $this->tenantA->id,
            'incident_type' => 'Noise Complaint',
            'description' => 'Explicit tenant id.',
            'location' => 'Purok 1',
            'incident_date' => now(),
            'complainant_name' => 'A',
            'respondent_name' => 'B',
            'status' => Incident::STATUS_OPEN,
            'submitted_online' => false,
        ]);

        $this->assertSame($this->tenantA->id, $incident->tenant_id);
    }

    // ---------------------------------------------------------------------
    // VerifyTenantSessionBinding middleware
    // ---------------------------------------------------------------------

    public function test_session_bound_to_tenant_a_is_rejected_when_resolved_tenant_is_b(): void
    {
        $user = User::factory()->create();
        Session::put('auth_tenant_id', $this->tenantA->id);

        // Simulate the attacker's request: the cookie was issued for A, but
        // we're now somehow on B's subdomain (shared cookie domain, session
        // replay, whatever). The middleware must refuse the request and
        // force a logout, regardless of how we got here.
        app()->instance('current_tenant', $this->tenantB);

        $middleware = app(VerifyTenantSessionBinding::class);

        $response = $middleware->handle(
            $this->makeAuthenticatedRequest($user),
            fn () => response('should_not_reach', 200),
        );

        $this->assertSame(
            302,
            $response->getStatusCode(),
            'A mismatched tenant binding must redirect to login, not render the page.',
        );
        $this->assertFalse(
            Session::has('auth_tenant_id'),
            'The attacker session state must be destroyed on rejection.',
        );
    }

    public function test_session_bound_to_tenant_a_is_accepted_when_resolved_tenant_is_a(): void
    {
        $user = User::factory()->create();
        Session::put('auth_tenant_id', $this->tenantA->id);
        app()->instance('current_tenant', $this->tenantA);

        $middleware = app(VerifyTenantSessionBinding::class);

        $reached = false;
        $response = $middleware->handle(
            $this->makeAuthenticatedRequest($user),
            function () use (&$reached) {
                $reached = true;
                return response('ok', 200);
            },
        );

        $this->assertTrue($reached, 'Legitimate tenant-matched requests must pass through.');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(
            Session::has('auth_tenant_id'),
            'A legitimate session must remain intact.',
        );
    }

    public function test_super_admin_session_is_rejected_if_tenant_context_leaks_in(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Session::put('auth_tenant_id', null); // super admins carry null
        app()->instance('current_tenant', $this->tenantA); // unexpected

        $middleware = app(VerifyTenantSessionBinding::class);

        $response = $middleware->handle(
            $this->makeAuthenticatedRequest($superAdmin),
            fn () => response('should_not_reach', 200),
        );

        $this->assertSame(
            302,
            $response->getStatusCode(),
            'A super-admin session must not be honored on a tenant subdomain — privilege bleed across contexts is dangerous.',
        );
    }

    // ---------------------------------------------------------------------
    // IncidentReportedToOfficialsMail: worker-safe payload
    // ---------------------------------------------------------------------

    public function test_incident_mail_survives_queue_serialization_without_tenant_context(): void
    {
        $mail = new IncidentReportedToOfficialsMail(
            incident: (object) [
                'id' => 42,
                'incident_type' => 'Noise',
                'description' => 'test desc',
                'location' => 'Purok 1',
                'incident_date' => now(),
                'complainant_name' => 'A',
                'respondent_name' => 'B',
                'status' => 'open',
            ],
            tenant: (object) ['name' => 'Tenant A'],
            reporter: (object) ['name' => 'Reporter', 'email' => 'reporter@example.test'],
            reporterRole: User::ROLE_RESIDENT,
        );

        // Drop ALL tenant context before round-tripping — this simulates a
        // queue worker picking the job up on a fresh process with no HTTP
        // request, no session, no resolved tenant. If the mailable tries
        // to touch the DB during rehydration, we'll see it here.
        $this->flushTenantContext();

        $revived = unserialize(serialize($mail));

        $this->assertInstanceOf(IncidentReportedToOfficialsMail::class, $revived);
        $this->assertSame('Tenant A', $revived->tenant->name);
        $this->assertSame(42, $revived->incident->id);
        $this->assertSame('reporter@example.test', $revived->reporter->email);
        $this->assertSame(User::ROLE_RESIDENT, $revived->reporterRole);
    }

    public function test_incident_mail_does_not_use_serializes_models_trait(): void
    {
        // If someone re-adds `SerializesModels`, the constructor accepting
        // Eloquent model properties will compile, queue serialization will
        // start re-querying the DB on the worker, and we're back to the
        // cross-connection bug. Fail fast at the reflection level.
        $traits = class_uses(IncidentReportedToOfficialsMail::class);

        $this->assertNotContains(
            \Illuminate\Queue\SerializesModels::class,
            $traits,
            'IncidentReportedToOfficialsMail must NOT use SerializesModels — it holds plain scalars so the queue worker never needs tenant context.',
        );
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function flushTenantContext(): void
    {
        if (app()->bound('current_tenant')) {
            app()->forgetInstance('current_tenant');
        }
        if (app()->bound('tenant_connection_name')) {
            app()->forgetInstance('tenant_connection_name');
        }
        Session::forget('current_tenant_id');
        Session::forget('auth_tenant_id');
    }

    private function makeAuthenticatedRequest(User $user): Request
    {
        $request = Request::create('/dummy', 'GET');
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
