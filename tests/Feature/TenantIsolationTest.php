<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private array $tenantDatabaseFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::query()->create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-' . Str::lower(Str::random(8)),
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

        $this->tenant = Tenant::query()->create([
            'plan_id' => $plan->id,
            'name' => 'Isolation Test Tenant',
            'slug' => 'isolation-test-' . Str::lower(Str::random(6)),
            'subdomain' => 'isotest' . Str::lower(Str::random(6)),
            'custom_domain' => null,
            'database_name' => 'tenant_test_' . Str::lower(Str::random(12)),
            'barangay' => 'Test Barangay',
            'address' => 'Test Address',
            'contact_phone' => '09170000000',
            'is_active' => true,
        ]);

        app(TenantDatabaseManager::class)->provisionTenantDatabase($this->tenant->fresh());

        $this->tenantDatabaseFiles[] = database_path('tenants/' . $this->tenant->database_name . '.sqlite');

        $this->user = User::query()->create([
            'name' => 'Isolation User',
            'email' => 'isolation-' . Str::lower(Str::random(8)) . '@example.com',
            'password' => Hash::make('password'),
            'is_super_admin' => false,
        ]);

        $this->user->tenants()->attach($this->tenant->id, ['role' => User::ROLE_PUROK_SECRETARY]);
    }

    protected function tearDown(): void
    {
        app(TenantDatabaseManager::class)->resetToCentralConnection();

        foreach ($this->tenantDatabaseFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    public function test_route_middleware_flow_sets_current_tenant_and_switches_connection(): void
    {
        Route::middleware(['web', 'auth', 'tenant', 'tenant.ensure', 'tenant.db'])
            ->get('/__test/tenant-db-switch', function () {
                return response()->json([
                    'tenant_id' => app('current_tenant')->id ?? null,
                    'default_connection' => DB::getDefaultConnection(),
                    'tenant_connection_binding' => app()->bound('tenant_connection_name') ? app('tenant_connection_name') : null,
                ]);
            });

        $response = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get('/__test/tenant-db-switch');

        $response->assertOk();
        $response->assertJson([
            'tenant_id' => $this->tenant->id,
            'default_connection' => 'tenant',
            'tenant_connection_binding' => 'tenant',
        ]);
    }

    public function test_central_models_still_write_to_central_when_tenant_connection_is_active(): void
    {
        app()->instance('current_tenant', $this->tenant);
        app(TenantDatabaseManager::class)->activateTenantConnection($this->tenant);

        $email = 'central-check-' . Str::lower(Str::random(8)) . '@example.com';

        $created = User::query()->create([
            'name' => 'Central Only User',
            'email' => $email,
            'password' => Hash::make('password'),
            'is_super_admin' => false,
        ]);

        $this->assertSame('central', $created->getConnectionName());
        $this->assertTrue(DB::connection('central')->table('users')->where('email', $email)->exists());
        $this->assertFalse(Schema::connection('tenant')->hasTable('users'));
    }

    public function test_tenant_models_write_to_tenant_connection_not_central(): void
    {
        app()->instance('current_tenant', $this->tenant);
        session(['current_tenant_id' => $this->tenant->id]);

        DB::setDefaultConnection('central');

        $incident = Incident::query()->create([
            'incident_type' => 'Noise Complaint',
            'description' => 'Loud videoke past curfew.',
            'location' => 'Purok 1',
            'incident_date' => now(),
            'complainant_name' => 'Complainant Test',
            'respondent_name' => 'Respondent Test',
            'status' => Incident::STATUS_OPEN,
            'submitted_online' => false,
        ]);

        $this->assertSame('tenant', $incident->getConnectionName());
        $this->assertTrue(DB::connection('tenant')->table('incidents')->where('id', $incident->id)->exists());
        $this->assertFalse(DB::connection('central')->table('incidents')->where('id', $incident->id)->exists());
    }

    public function test_tenant_debug_command_prints_tenant_and_connection_snapshot(): void
    {
        $this->artisan('tenant:debug', ['tenant' => (string) $this->tenant->id])
            ->expectsOutputToContain('TENANCY DEBUG SNAPSHOT')
            ->expectsOutputToContain('Tenant ID')
            ->expectsOutputToContain('Tenant Database Name')
            ->expectsOutputToContain($this->tenant->database_name)
            ->expectsOutputToContain('Runtime Default Connection')
            ->expectsOutputToContain('tenant')
            ->assertExitCode(0);
    }
}
