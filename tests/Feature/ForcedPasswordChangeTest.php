<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_forced_password_change_can_still_open_get_routes(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/tenant/select');

        $response->assertOk();
    }

    public function test_user_with_forced_password_change_cannot_submit_mutating_requests(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'must_change_password' => true,
        ]);

        $response = $this->from('/tenant/select')->actingAs($user)->post('/tenant/select', [
            'tenant_id' => 1,
        ]);

        $response->assertRedirect('/tenant/select');
        $response->assertSessionHas('error', 'You must change your password before continuing.');
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
}
