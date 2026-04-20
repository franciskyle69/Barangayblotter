<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::where('email', 'admin')->update(['email' => 'admin@admin']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
                'is_super_admin' => true,
            ]
        );

        // Attach admin to all barangays so they can access and report incidents
        foreach (Tenant::all() as $tenant) {
            if (!$tenant->users()->where('users.id', $admin->id)->exists()) {
                $tenant->users()->attach($admin->id, ['role' => User::ROLE_PUROK_SECRETARY]);
            }
        }
    }
}
