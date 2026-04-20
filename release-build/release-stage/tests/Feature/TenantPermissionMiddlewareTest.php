<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantPermissionMiddlewareTest extends TestCase
{
    use DatabaseMigrations;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionRoleSeeder::class);

        $plan = Plan::query()->create([
            'name' => 'RBAC Test Plan',
            'slug' => 'rbac-test-plan-' . Str::lower(Str::random(8)),
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
            'name' => 'RBAC Tenant',
            'slug' => 'rbac-tenant-' . Str::lower(Str::random(6)),
            'subdomain' => null,
            'custom_domain' => null,
            'database_name' => null,
            'barangay' => 'Test Barangay',
            'address' => 'Test Address',
            'contact_phone' => '09170000000',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('central');
        DB::disconnect('sqlite');
        DB::disconnect('tenant');

        parent::tearDown();
    }

    public function test_tenant_permission_middleware_denies_user_without_required_permission(): void
    {
        Route::middleware(['web', 'auth', 'tenant', 'tenant.ensure', 'tenant.permission:manage_users'])
            ->get('/__test/rbac/deny', fn() => response()->json(['ok' => true]));

        $user = User::query()->create([
            'name' => 'Resident User',
            'email' => 'resident-' . Str::lower(Str::random(8)) . '@example.com',
            'password' => Hash::make('password'),
            'is_super_admin' => false,
        ]);

        $user->tenants()->attach($this->tenant->id, ['role' => User::ROLE_RESIDENT]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get('/__test/rbac/deny');

        $response->assertForbidden();
    }

    public function test_tenant_permission_middleware_allows_user_with_required_permission(): void
    {
        Route::middleware(['web', 'auth', 'tenant', 'tenant.ensure', 'tenant.permission:manage_users'])
            ->get('/__test/rbac/allow', fn() => response()->json(['ok' => true]));

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin-' . Str::lower(Str::random(8)) . '@example.com',
            'password' => Hash::make('password'),
            'is_super_admin' => false,
        ]);

        $user->tenants()->attach($this->tenant->id, ['role' => User::ROLE_BARANGAY_ADMIN]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get('/__test/rbac/allow');

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    public function test_tenant_permission_lookup_uses_central_rbac_even_when_default_connection_is_tenant(): void
    {
        $user = User::query()->create([
            'name' => 'Admin User 2',
            'email' => 'admin2-' . Str::lower(Str::random(8)) . '@example.com',
            'password' => Hash::make('password'),
            'is_super_admin' => false,
        ]);

        $user->tenants()->attach($this->tenant->id, ['role' => User::ROLE_BARANGAY_ADMIN]);

        config([
            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('tenant');
        $previousDefaultConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('tenant');

        try {
            $this->assertTrue($user->hasTenantPermission($this->tenant, 'manage_users'));
            $this->assertTrue($user->hasTenantPermission($this->tenant, ['manage_users', 'manage_incidents']));
            $this->assertFalse($user->hasTenantPermission($this->tenant, ['manage_users', 'nonexistent.permission'], true));
        } finally {
            DB::setDefaultConnection($previousDefaultConnection);
        }
    }
}
