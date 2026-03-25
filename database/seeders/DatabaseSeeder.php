<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
                // TenantSeeder::class, // ⚠️ DISABLED - No automatic tenant seeding
                // Create tenants manually via:
                // - Web UI: /super/tenants/create
                // - CLI: php artisan tenant:create
                // - Or restore samples: php artisan db:seed --class=TenantSeeder
            UserSeeder::class,
        ]);
    }
}
