<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BackfillBarangayAdminsCommand extends Command
{
    protected $signature = 'tenant:backfill-barangay-admins {--dry-run : Show the changes without saving them}';

    protected $description = 'Ensure every tenant has at least one Barangay Admin.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenants = Tenant::query()->with([
            'users' => fn($query) => $query->orderBy('tenant_user.created_at')->orderBy('users.id'),
        ])->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');
            return self::SUCCESS;
        }

        $fixed = 0;
        $created = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            $adminCount = $tenant->users()->wherePivot('role', User::ROLE_BARANGAY_ADMIN)->count();

            if ($adminCount > 0) {
                $skipped++;
                $this->line("[SKIP] {$tenant->name} already has Barangay Admin.");
                continue;
            }

            $firstUser = $tenant->users->first();

            if ($firstUser) {
                $this->line("[FIX] {$tenant->name}: promoting {$firstUser->name} to Barangay Admin.");

                if (!$dryRun) {
                    $tenant->users()->updateExistingPivot($firstUser->id, [
                        'role' => User::ROLE_BARANGAY_ADMIN,
                    ]);
                }

                $fixed++;
                continue;
            }

            $baseSlug = Str::slug($tenant->slug ?: $tenant->name) ?: 'tenant';
            $email = "barangay-admin+tenant-{$tenant->id}-{$baseSlug}@local.invalid";
            $suffix = 1;

            while (User::where('email', $email)->exists()) {
                $email = "barangay-admin+tenant-{$tenant->id}-{$baseSlug}-{$suffix}@local.invalid";
                $suffix++;
            }

            $password = Str::random(16);
            $displayName = $tenant->name . ' Barangay Admin';

            $this->line("[CREATE] {$tenant->name}: creating placeholder Barangay Admin account ({$email}).");

            if (!$dryRun) {
                $user = User::create([
                    'name' => $displayName,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'is_super_admin' => false,
                ]);

                $tenant->users()->attach($user->id, [
                    'role' => User::ROLE_BARANGAY_ADMIN,
                ]);
            }

            $created++;
        }

        $this->newLine();
        $this->info("Done. Fixed: {$fixed}, created: {$created}, skipped: {$skipped}." . ($dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}