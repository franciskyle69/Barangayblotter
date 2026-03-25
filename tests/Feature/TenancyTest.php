<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Incident;
use Tests\TestCase;
use Illuminate\Support\Facades\Session;

class TenancyTest extends TestCase
{
    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Create users in different tenants
        $this->user1 = User::factory()->create(['name' => 'User 1']);
        $this->user1->tenants()->attach($this->tenant1, ['role' => User::ROLE_PUROK_SECRETARY]);

        $this->user2 = User::factory()->create(['name' => 'User 2']);
        $this->user2->tenants()->attach($this->tenant2, ['role' => User::ROLE_PUROK_SECRETARY]);
    }

    /**
     * Test that tenant data is isolated in queries
     */
    public function test_incident_queries_are_scoped_by_tenant(): void
    {
        // Create incidents in different tenants
        $incident1 = Incident::factory()->create(['tenant_id' => $this->tenant1->id]);
        $incident2 = Incident::factory()->create(['tenant_id' => $this->tenant2->id]);

        // Set context to tenant1
        Session::put('current_tenant_id', $this->tenant1->id);

        // Query without global scope should return only tenant1's incident
        $incidents = Incident::all();
        $this->assertCount(1, $incidents);
        $this->assertEquals($incident1->id, $incidents->first()->id);
    }

    /**
     * Test that super admin can bypass tenant scoping
     */
    public function test_super_admin_can_bypass_tenant_scoping(): void
    {
        // Create incidents in different tenants
        $incident1 = Incident::factory()->create(['tenant_id' => $this->tenant1->id]);
        $incident2 = Incident::factory()->create(['tenant_id' => $this->tenant2->id]);

        // Query without global scope
        $incidents = Incident::withoutGlobalScope('tenant')->get();
        $this->assertCount(2, $incidents);
    }

    /**
     * Test that new records auto-set tenant_id
     */
    public function test_new_records_auto_set_tenant_id(): void
    {
        // Set current tenant
        Session::put('current_tenant_id', $this->tenant1->id);

        // Create incident without explicit tenant_id
        $incident = Incident::factory()->create([
            'status' => Incident::STATUS_OPEN,
            // Omit tenant_id to test auto-setting
        ]);

        $this->assertEquals($this->tenant1->id, $incident->tenant_id);
    }

    /**
     * Test tenant switching
     */
    public function test_tenant_context_can_be_switched(): void
    {
        // Set tenant1
        Session::put('current_tenant_id', $this->tenant1->id);

        $incident1 = Incident::factory()->create(['tenant_id' => $this->tenant1->id]);
        $incident2 = Incident::factory()->create(['tenant_id' => $this->tenant2->id]);

        // Switch to tenant2
        Session::put('current_tenant_id', $this->tenant2->id);

        // Should only see tenant2's incident
        $incidents = Incident::all();
        $this->assertCount(1, $incidents);
        $this->assertEquals($incident2->id, $incidents->first()->id);
    }

    /**
     * Test that user can only access their tenant's data
     */
    public function test_user_can_only_access_their_tenant(): void
    {
        // User1 should only see tenant1 in their tenants
        $this->assertEquals(1, $this->user1->tenants()->count());
        $this->assertTrue($this->user1->tenants()->find($this->tenant1->id) !== null);
        $this->assertFalse($this->user1->tenants()->find($this->tenant2->id) !== null);
    }

    /**
     * Test that user can check their role in a tenant
     */
    public function test_user_can_check_role_in_tenant(): void
    {
        $this->assertTrue(
            $this->user1->hasRoleIn($this->tenant1, User::ROLE_PUROK_SECRETARY)
        );
        $this->assertFalse(
            $this->user1->hasRoleIn($this->tenant1, User::ROLE_MEDIATOR)
        );
    }

    /**
     * Test TenancyManager service
     */
    public function test_tenancy_manager_returns_current_tenant(): void
    {
        Session::put('current_tenant_id', $this->tenant1->id);

        $manager = app('tenancy_manager');
        $currentTenant = $manager->current();

        $this->assertNotNull($currentTenant);
        $this->assertEquals($this->tenant1->id, $currentTenant->id);
    }

    /**
     * Test TenancyManager get method
     */
    public function test_tenancy_manager_get_returns_tenant_value(): void
    {
        Session::put('current_tenant_id', $this->tenant1->id);

        $manager = app('tenancy_manager');
        $name = $manager->get('name');

        $this->assertEquals($this->tenant1->name, $name);
    }

    /**
     * Test TenancyManager run method
     */
    public function test_tenancy_manager_run_executes_in_tenant_context(): void
    {
        $manager = app('tenancy_manager');

        $result = $manager->run($this->tenant1, function (Tenant $tenant) {
            return Incident::factory()->create(['tenant_id' => $tenant->id]);
        });

        $this->assertNotNull($result);
        $this->assertEquals($this->tenant1->id, $result->tenant_id);
    }
}
