<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            // TenantSeeder::class, // Disabled: Create tenants manually via 'php artisan tenant:create' command
            UserSeeder::class,
        ]);
    }
}
