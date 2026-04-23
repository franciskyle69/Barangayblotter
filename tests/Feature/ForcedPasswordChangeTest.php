<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Users flagged `must_change_password` used to be allowed through
     * any GET route (only write verbs were blocked). That let them
     * browse dashboards and read data while their temporary password
     * was still active. EnforcePasswordChange now blocks ALL methods
     * except the password-change flow + logout, and GETs are bounced
     * to the dedicated force-change page.
     */
    public function test_user_with_forced_password_change_is_redirected_to_force_change_page(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/tenant/select');

        $response->assertRedirect('/password/force-change');
    }

    public function test_force_change_page_itself_is_accessible(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/password/force-change');

        $response->assertOk();
    }

    public function test_user_with_forced_password_change_cannot_submit_mutating_requests(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
        ]);

        $response = $this->from('/tenant/select')
            ->actingAs($user)
            ->post('/tenant/select', ['tenant_id' => 1]);

        // All non-allowed routes (including write verbs) now redirect
        // to the force-change page — the old behavior redirected back
        // with a flash error which let users infer the flag was still
        // set but otherwise stay on the previous screen. The new flow
        // is stricter and more consistent.
        $response->assertRedirect('/password/force-change');
    }

    public function test_force_change_page_redirects_back_to_dashboard_if_flag_is_cleared(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/password/force-change');

        $response->assertRedirect('/dashboard');
    }

    public function test_forced_password_change_endpoint_updates_password_and_unlocks_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->put('/password/force-change', [
            'current_password' => 'TempPass123!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewSecurePass123!', $user->password));
    }

    public function test_inertia_xhr_request_during_forced_change_is_not_allowed_through(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
        ]);

        // Inertia's middleware intercepts our JSON 423 and converts it
        // into its own convention (409 + X-Inertia-Location, or 302).
        // Either way, the important property is that the response is
        // NOT a successful 200 render of the target page. The user
        // MUST NOT receive page content while the flag is set.
        $response = $this->actingAs($user)
            ->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
            ->get('/tenant/select');

        $this->assertNotEquals(
            200,
            $response->getStatusCode(),
            'A 200 response would mean the page rendered — forced-password-change bypass.',
        );
    }
}
