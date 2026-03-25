<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $basic = Plan::where('slug', 'basic')->first();
        $standard = Plan::where('slug', 'standard')->first();
        $premium = Plan::where('slug', 'premium')->first();

        // Malaybalay City, Bukidnon — sample barangays by district
        $tenants = [
            ['name' => 'Casisang', 'slug' => 'casisang', 'subdomain' => 'casisang', 'barangay' => 'South Highway', 'plan' => $basic],
            ['name' => 'Sumpong', 'slug' => 'sumpong', 'subdomain' => 'sumpong', 'barangay' => 'North Highway', 'plan' => $standard],
            ['name' => 'San Jose', 'slug' => 'san-jose', 'subdomain' => 'sanjose', 'barangay' => 'South Highway', 'plan' => $premium],
            ['name' => 'Kalasungay', 'slug' => 'kalasungay', 'subdomain' => 'kalasungay', 'barangay' => 'North Highway', 'plan' => $standard],
            ['name' => 'Caburacanan', 'slug' => 'caburacanan', 'subdomain' => 'caburacanan', 'barangay' => 'Upper Pulangi', 'plan' => $basic],
        ];

        $admin = User::firstOrCreate(
            ['email' => 'admin@malaybalay.test'],
            [
                'name' => 'Barangay Admin',
                'password' => Hash::make('password'),
                'phone' => null,
                'is_super_admin' => false,
            ]
        );

        $superAdmin = User::firstOrCreate(
            ['email' => 'city@malaybalay.test'],
            [
                'name' => 'Malaybalay City Admin',
                'password' => Hash::make('password'),
                'phone' => null,
                'is_super_admin' => true,
            ]
        );

        foreach ($tenants as $t) {
            $tenant = Tenant::firstOrCreate(
                ['slug' => $t['slug']],
                [
                    'plan_id' => $t['plan']->id,
                    'name' => $t['name'],
                    'subdomain' => $t['subdomain'],
                    'barangay' => $t['barangay'],
                    'address' => $t['name'] . ', Malaybalay City, Bukidnon',
                    'contact_phone' => null,
                    'is_active' => true,
                ]
            );

            if (!$tenant->users()->where('users.id', $admin->id)->exists()) {
                $tenant->users()->attach($admin->id, ['role' => User::ROLE_PUROK_SECRETARY]);
            }
        }

        if (!$superAdmin->tenants()->exists()) {
            $superAdmin->tenants()->attach(Tenant::first()->id, ['role' => User::ROLE_PUROK_LEADER]);
        }
    }
}
