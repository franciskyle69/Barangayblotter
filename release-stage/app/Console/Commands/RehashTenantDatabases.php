<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RehashTenantDatabases extends Command
{
    protected $signature = 'tenants:rehash-databases {--force : Skip confirmation prompt}';

    protected $description = 'Rehash existing tenant database names for security (SHA256 based)';

    public function handle(): int
    {
        $this->info('Tenant Database Rehashing');
        $this->line('');

        $tenants = Tenant::whereNotNull('database_name')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants with databases found.');
            return 0;
        }

        $this->info("Found {$tenants->count()} tenant(s) to process:");
        $this->line('');

        foreach ($tenants as $tenant) {
            $newName = $this->generateHashedName($tenant->id);
            $this->line("  ID {$tenant->id}: {$tenant->database_name} → {$newName}");
        }

        $this->line('');

        if (!$this->option('force') && !$this->confirm('Continue with rehashing?', false)) {
            $this->info('Cancelled.');
            return 0;
        }

        $this->line('');
        $this->info('Starting rehash operation...');
        $this->line('');

        $successful = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                $oldName = $tenant->database_name;
                $newName = $this->generateHashedName($tenant->id);

                if ($oldName === $newName) {
                    $this->line("  ✓ Tenant {$tenant->id}: Already hashed, skipping");
                    $successful++;
                    continue;
                }

                // Clone database with new hashed name
                $this->cloneDatabase($oldName, $newName);

                // Update tenant record
                $tenant->update(['database_name' => $newName]);

                // Drop old database
                $this->dropDatabase($oldName);

                $this->line("  ✓ Tenant {$tenant->id}: {$oldName} → {$newName}");
                $successful++;
            } catch (RuntimeException $e) {
                $this->error("  ✗ Tenant {$tenant->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->line('');
        $this->info("Completed: {$successful} successful, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }

    private function generateHashedName(int|string $tenantId): string
    {
        $hash = substr(hash('sha256', (string) $tenantId), 0, 12);
        return 'tenant_' . $hash;
    }

    private function cloneDatabase(string $sourceName, string $targetName): void
    {
        $driver = config('database.default');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("CREATE DATABASE `{$targetName}`");
            DB::statement("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = ?", [$sourceName]);

            $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$sourceName]);

            foreach ($tables as $table) {
                $tableName = $table->TABLE_NAME;
                DB::statement("CREATE TABLE `{$targetName}`.`{$tableName}` LIKE `{$sourceName}`.`{$tableName}`");
                DB::statement("INSERT INTO `{$targetName}`.`{$tableName}` SELECT * FROM `{$sourceName}`.`{$tableName}`");
            }
        } elseif ($driver === 'pgsql') {
            DB::statement("CREATE DATABASE \"{$targetName}\" TEMPLATE \"{$sourceName}\"");
        } else {
            throw new RuntimeException("Unsupported database driver: {$driver}");
        }
    }

    private function dropDatabase(string $databaseName): void
    {
        $driver = config('database.default');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("DROP DATABASE `{$databaseName}`");
        } elseif ($driver === 'pgsql') {
            DB::statement("DROP DATABASE IF EXISTS \"{$databaseName}\"");
        }
    }
}
