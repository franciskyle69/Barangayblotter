<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateTenantUsersCommand extends Command
{
    protected $signature = 'tenant:migrate-users {--purge-central : Remove migrated tenant users and pivot rows from the central database after copying}';

    protected $description = 'Copy central tenant user accounts into each tenant database.';

    public function handle(TenantDatabaseManager $tenantDatabases): int
    {
        $purgeCentral = (bool) $this->option('purge-central');
        $centralConnection = config('tenancy.central_connection', 'central');
        $tenants = Tenant::query()->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $migratedCount = $tenantDatabases->runInTenantContext($tenant, function () use ($tenant, $centralConnection): int {
                $members = DB::connection($centralConnection)
                    ->table('tenant_user')
                    ->join('users', 'tenant_user.user_id', '=', 'users.id')
                    ->where('tenant_user.tenant_id', $tenant->id)
                    ->select([
                        'users.name',
                        'users.email',
                        'users.phone',
                        'users.password',
                        'users.is_super_admin',
                        'users.must_change_password',
                        'tenant_user.role as role',
                    ])
                    ->orderBy('tenant_user.created_at')
                    ->get();

                $count = 0;

                foreach ($members as $member) {
                    if ($member->is_super_admin) {
                        continue;
                    }

                    User::updateOrCreate(
                        ['email' => $member->email],
                        [
                            'name' => $member->name,
                            'phone' => $member->phone,
                            'password' => $member->password,
                            'role' => $member->role ?: User::ROLE_CITIZEN,
                            'is_super_admin' => false,
                            'must_change_password' => (bool) $member->must_change_password,
                        ]
                    );

                    $count++;
                }

                return $count;
            });

            $this->line(sprintf('[OK] %s: migrated %d user(s).', $tenant->name, $migratedCount));
        }

        if (!$purgeCentral) {
            $this->warn('Central tenant-user rows were preserved. Re-run with --purge-central after verifying the tenant databases.');
            return self::SUCCESS;
        }

        $this->warn('Purging migrated tenant-user rows from the central database.');

        try {
            DB::connection($centralConnection)->transaction(function () use ($tenants, $centralConnection): void {
                $tenantIds = $tenants->pluck('id')->all();

                $tenantUserIds = DB::connection($centralConnection)
                    ->table('users')
                    ->join('tenant_user', 'users.id', '=', 'tenant_user.user_id')
                    ->whereIn('tenant_user.tenant_id', $tenantIds)
                    ->where('users.is_super_admin', false)
                    ->pluck('users.id')
                    ->unique()
                    ->values()
                    ->all();

                DB::connection($centralConnection)
                    ->table('tenant_user')
                    ->whereIn('tenant_id', $tenantIds)
                    ->delete();

                if ($tenantUserIds !== []) {
                    DB::connection($centralConnection)
                        ->table('users')
                        ->whereIn('id', $tenantUserIds)
                        ->delete();
                }
            });
        } catch (Throwable $e) {
            report($e);
            $this->error('Central purge failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Central tenant users have been purged.');
        return self::SUCCESS;
    }
}
