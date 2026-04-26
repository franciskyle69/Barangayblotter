<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackupService
{
    private const BACKUP_DIRECTORY = 'backups/database';

    public function __construct(private TenantDatabaseManager $tenantDatabases)
    {
    }

    public function createBackup(?int $onlyTenantId = null): array
    {
        $payload = $this->buildSnapshot($onlyTenantId);

        $filename = $onlyTenantId
            ? ('tenant-backup-' . $onlyTenantId . '-' . now()->format('Ymd_His') . '.json')
            : ('full-backup-' . now()->format('Ymd_His') . '.json');
        $relativePath = $this->relativePathFor($filename);

        Storage::disk('local')->makeDirectory(self::BACKUP_DIRECTORY);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Failed to encode backup payload to JSON.');
        }

        Storage::disk('local')->put($relativePath, $json);

        return [
            'filename' => $filename,
            'relative_path' => $relativePath,
            'absolute_path' => Storage::disk('local')->path($relativePath),
        ];
    }

    public function listBackups(): array
    {
        if (!Storage::disk('local')->exists(self::BACKUP_DIRECTORY)) {
            return [];
        }

        $files = collect(Storage::disk('local')->files(self::BACKUP_DIRECTORY))
            ->filter(fn(string $path) => str_ends_with(strtolower($path), '.json'))
            ->map(function (string $path) {
                $timestamp = Storage::disk('local')->lastModified($path);
                $size = Storage::disk('local')->size($path);
                $summary = $this->summarizeBackupFile($path);

                return array_merge([
                    'filename' => basename($path),
                    'size_bytes' => $size,
                    'size_human' => $this->formatBytes($size),
                    'last_modified' => $timestamp,
                    'last_modified_iso' => now()->setTimestamp($timestamp)->toIso8601String(),
                    'last_modified_human' => now()->setTimestamp($timestamp)->format('M d, Y h:i A'),
                ], $summary);
            })
            ->sortByDesc('last_modified')
            ->values();

        return $files->all();
    }

    public function absoluteBackupDirectory(): string
    {
        Storage::disk('local')->makeDirectory(self::BACKUP_DIRECTORY);

        return Storage::disk('local')->path(self::BACKUP_DIRECTORY);
    }

    public function backupExists(string $filename): bool
    {
        $safe = $this->assertSafeFilename($filename);

        return Storage::disk('local')->exists($this->relativePathFor($safe));
    }

    public function relativePathFor(string $filename): string
    {
        $safe = $this->assertSafeFilename($filename);

        return self::BACKUP_DIRECTORY . '/' . $safe;
    }

    public function restoreFromStoredFile(string $filename): void
    {
        $relativePath = $this->relativePathFor($filename);

        if (!Storage::disk('local')->exists($relativePath)) {
            throw new RuntimeException('Backup file was not found.');
        }

        $json = Storage::disk('local')->get($relativePath);
        $payload = $this->decodePayload($json);

        $this->restoreSnapshot($payload);
    }

    public function restoreFromJsonString(string $json): void
    {
        $payload = $this->decodePayload($json);

        $this->restoreSnapshot($payload);
    }

    private function buildSnapshot(?int $onlyTenantId = null): array
    {
        $centralConnection = config('tenancy.central_connection', 'central');

        $snapshot = [
            'meta' => [
                'version' => 2,
                'generated_at' => now()->toIso8601String(),
                'app_name' => config('app.name'),
                'central_connection' => $centralConnection,
                'central_driver' => $this->driverForConnection($centralConnection),
                'tenant_backup_errors' => [],
                'only_tenant_id' => $onlyTenantId,
            ],
            'central' => $this->dumpConnection($centralConnection),
            'standalone_tenant' => null,
            'tenants' => [],
        ];

        $standaloneDatabaseName = $this->standaloneTenantDatabaseName();
        if ($standaloneDatabaseName) {
            try {
                $standaloneConnection = $this->configureTenantConnection('backup_standalone_tenant', $standaloneDatabaseName);

                $snapshot['standalone_tenant'] = [
                    'database_name' => $standaloneDatabaseName,
                    'driver' => $this->driverForConnection($standaloneConnection),
                    'data' => $this->dumpConnection($standaloneConnection),
                ];
            } catch (\Throwable $e) {
                $snapshot['meta']['tenant_backup_errors'][] = [
                    'type' => 'standalone_tenant',
                    'database_name' => $standaloneDatabaseName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $tenantsQuery = Tenant::query()
            ->select(['id', 'name', 'database_name'])
            ->orderBy('id');

        if ($onlyTenantId && $onlyTenantId > 0) {
            $tenantsQuery->where('id', $onlyTenantId);
        }

        $tenants = $tenantsQuery->get();

        foreach ($tenants as $tenant) {
            $databaseName = $this->resolveTenantDatabaseName($tenant);
            if (!$databaseName) {
                continue;
            }

            try {
                $tenantConnection = $this->configureTenantConnection('backup_tenant', $databaseName);

                $snapshot['tenants'][] = [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'database_name' => $databaseName,
                    'driver' => $this->driverForConnection($tenantConnection),
                    'data' => $this->dumpConnection($tenantConnection),
                ];
            } catch (\Throwable $e) {
                $snapshot['meta']['tenant_backup_errors'][] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'database_name' => $databaseName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $snapshot;
    }

    private function dumpConnection(string $connection): array
    {
        $driver = $this->driverForConnection($connection);
        $tables = collect(Schema::connection($connection)->getTableListing())
            ->filter(fn(string $table) => !$this->shouldSkipTable($driver, $table))
            ->values();

        $tableData = [];

        foreach ($tables as $table) {
            $rows = DB::connection($connection)
                ->table($table)
                ->get()
                ->map(fn($row) => (array) $row)
                ->all();

            $tableData[$table] = $rows;
        }

        return [
            'driver' => $driver,
            'tables' => $tableData,
            'table_count' => $tables->count(),
        ];
    }

    private function restoreSnapshot(array $payload): void
    {
        $centralConnection = config('tenancy.central_connection', 'central');

        $centralTables = data_get($payload, 'central.tables');
        if (!is_array($centralTables)) {
            throw new RuntimeException('Backup file is invalid: central tables are missing.');
        }

        DB::connection($centralConnection)->beginTransaction();

        try {
            $this->restoreConnectionData($centralConnection, $centralTables);
            DB::connection($centralConnection)->commit();
        } catch (\Throwable $e) {
            DB::connection($centralConnection)->rollBack();
            throw $e;
        }

        $standaloneTenantTables = data_get($payload, 'standalone_tenant.data.tables');
        $standaloneTenantDatabaseName = data_get($payload, 'standalone_tenant.database_name');

        if (is_array($standaloneTenantTables) && is_string($standaloneTenantDatabaseName) && trim($standaloneTenantDatabaseName) !== '') {
            $standaloneConnection = $this->configureTenantConnection('backup_standalone_tenant_restore', trim($standaloneTenantDatabaseName));
            $this->restoreConnectionData($standaloneConnection, $standaloneTenantTables);
        }

        $tenantBackups = data_get($payload, 'tenants', []);
        if (!is_array($tenantBackups)) {
            return;
        }

        foreach ($tenantBackups as $tenantBackup) {
            if (!is_array($tenantBackup)) {
                continue;
            }

            $tenantTables = data_get($tenantBackup, 'data.tables');
            $tenantId = data_get($tenantBackup, 'id');
            $tenant = is_numeric($tenantId) ? Tenant::query()->find((int) $tenantId) : null;
            $databaseName = $this->resolveTenantDatabaseNameForRestore($tenantBackup, $tenant);

            if (!is_array($tenantTables) || !is_string($databaseName) || trim($databaseName) === '') {
                continue;
            }

            $this->prepareTenantDatabaseForRestore($tenant, $databaseName);

            $tenantConnection = $this->configureTenantConnection('backup_tenant_restore', $databaseName);
            $this->restoreConnectionData($tenantConnection, $tenantTables);
        }
    }

    private function prepareTenantDatabaseForRestore(?Tenant $tenant, string $databaseName): void
    {
        if (!$tenant) {
            return;
        }

        if ($tenant->database_name !== $databaseName) {
            $tenant->database_name = $databaseName;
            $tenant->save();
        }

        try {
            $this->tenantDatabases->provisionTenantDatabase($tenant);
        } catch (\Throwable $e) {
            // Provisioning is a best effort: restore may still succeed if schema already exists.
            report($e);
        }
    }

    private function resolveTenantDatabaseName(object $tenant): ?string
    {
        $configured = is_string($tenant->database_name ?? null) ? trim($tenant->database_name) : '';
        if ($configured !== '') {
            return $configured;
        }

        $tenantId = isset($tenant->id) ? (int) $tenant->id : 0;
        if ($tenantId <= 0) {
            return null;
        }

        return $this->defaultTenantDatabaseName($tenantId);
    }

    private function resolveTenantDatabaseNameForRestore(array $tenantBackup, ?Tenant $tenant): ?string
    {
        $fromBackup = data_get($tenantBackup, 'database_name');
        if (is_string($fromBackup) && trim($fromBackup) !== '') {
            return trim($fromBackup);
        }

        if ($tenant) {
            $fromTenant = is_string($tenant->database_name) ? trim($tenant->database_name) : '';
            if ($fromTenant !== '') {
                return $fromTenant;
            }
        }

        $tenantId = data_get($tenantBackup, 'id');
        if (is_numeric($tenantId)) {
            return $this->defaultTenantDatabaseName((int) $tenantId);
        }

        return null;
    }

    private function defaultTenantDatabaseName(int $tenantId): string
    {
        return 'tenant_' . $tenantId;
    }

    private function standaloneTenantDatabaseName(): ?string
    {
        $configured = config('tenancy.standalone_database');

        $candidates = [];
        if (is_string($configured) && trim($configured) !== '') {
            $candidates[] = trim($configured);
        }

        // Legacy/common names to support zero-config setups.
        $candidates[] = 'tenant';
        $candidates[] = 'tenants';

        foreach (array_unique($candidates) as $candidate) {
            if ($this->databaseExists($candidate)) {
                return $candidate;
            }
        }

        return $this->detectStandaloneTenantDatabaseName();
    }

    private function databaseExists(string $databaseName): bool
    {
        $databaseName = trim($databaseName);
        if ($databaseName === '') {
            return false;
        }

        $centralConnection = config('tenancy.central_connection', 'central');
        $driver = $this->driverForConnection($centralConnection);

        try {
            return match ($driver) {
                'mysql', 'mariadb' => (bool) DB::connection($centralConnection)
                    ->table('information_schema.schemata')
                    ->where('schema_name', $databaseName)
                    ->exists(),
                'pgsql' => (bool) DB::connection($centralConnection)
                    ->table('pg_database')
                    ->where('datname', $databaseName)
                    ->exists(),
                'sqlite' => File::exists(database_path($databaseName))
                || File::exists(database_path($databaseName . '.sqlite')),
                default => false,
            };
        } catch (\Throwable) {
            return false;
        }
    }

    private function detectStandaloneTenantDatabaseName(): ?string
    {
        $centralConnection = config('tenancy.central_connection', 'central');
        $driver = $this->driverForConnection($centralConnection);

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return null;
        }

        $centralDatabase = (string) config("database.connections.{$centralConnection}.database", '');
        $reservedSchemas = array_filter([
            strtolower($centralDatabase),
            'information_schema',
            'mysql',
            'performance_schema',
            'sys',
            'phpmyadmin',
        ]);

        try {
            $rows = DB::connection($centralConnection)->select(
                "SELECT table_schema AS schema_name, COUNT(*) AS match_count
                 FROM information_schema.tables
                 WHERE table_name REGEXP '^tenant_[0-9]+$'
                 GROUP BY table_schema
                 ORDER BY match_count DESC"
            );

            foreach ($rows as $row) {
                $schemaName = (string) ($row->schema_name ?? '');
                if ($schemaName === '') {
                    continue;
                }

                if (in_array(strtolower($schemaName), $reservedSchemas, true)) {
                    continue;
                }

                return $schemaName;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function restoreConnectionData(string $connection, array $tableData): void
    {
        $driver = $this->driverForConnection($connection);

        $existingTables = collect(Schema::connection($connection)->getTableListing())
            ->filter(fn(string $table) => !$this->shouldSkipTable($driver, $table))
            ->values();

        $tablesToRestore = $existingTables
            ->filter(fn(string $table) => array_key_exists($table, $tableData))
            ->values();

        $this->disableForeignKeys($connection, $driver);

        try {
            foreach ($tablesToRestore as $table) {
                $this->clearTable($connection, $driver, $table);
            }

            foreach ($tablesToRestore as $table) {
                $rows = $tableData[$table] ?? [];

                if (!is_array($rows) || empty($rows)) {
                    continue;
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    $cleanChunk = array_map(function ($row) {
                        return is_array($row) ? $row : (array) $row;
                    }, $chunk);

                    DB::connection($connection)->table($table)->insert($cleanChunk);
                }
            }
        } finally {
            $this->enableForeignKeys($connection, $driver);
        }
    }

    private function configureTenantConnection(string $alias, string $databaseName): string
    {
        $centralConnection = config('tenancy.central_connection', 'central');
        $baseConfig = config("database.connections.{$centralConnection}");

        if (!is_array($baseConfig)) {
            throw new RuntimeException('Unable to resolve central database configuration.');
        }

        $driver = $baseConfig['driver'] ?? null;
        if (!$driver) {
            throw new RuntimeException('Database driver is missing from central configuration.');
        }

        $databaseValue = $databaseName;

        if ($driver === 'sqlite') {
            $filename = str_ends_with($databaseName, '.sqlite') ? $databaseName : ($databaseName . '.sqlite');
            $databaseValue = database_path('tenants/' . $filename);

            $directory = dirname($databaseValue);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            if (!File::exists($databaseValue)) {
                File::put($databaseValue, '');
            }
        }

        config([
            "database.connections.{$alias}" => array_merge($baseConfig, [
                'database' => $databaseValue,
            ]),
        ]);

        DB::purge($alias);

        return $alias;
    }

    private function driverForConnection(string $connection): string
    {
        return (string) config("database.connections.{$connection}.driver", 'unknown');
    }

    private function disableForeignKeys(string $connection, string $driver): void
    {
        switch ($driver) {
            case 'sqlite':
                DB::connection($connection)->statement('PRAGMA foreign_keys = OFF');
                break;
            case 'mysql':
            case 'mariadb':
                DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=0');
                break;
            case 'pgsql':
                DB::connection($connection)->statement('SET session_replication_role = replica');
                break;
        }
    }

    private function enableForeignKeys(string $connection, string $driver): void
    {
        switch ($driver) {
            case 'sqlite':
                DB::connection($connection)->statement('PRAGMA foreign_keys = ON');
                break;
            case 'mysql':
            case 'mariadb':
                DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=1');
                break;
            case 'pgsql':
                DB::connection($connection)->statement('SET session_replication_role = DEFAULT');
                break;
        }
    }

    private function clearTable(string $connection, string $driver, string $table): void
    {
        $quotedTable = $this->quoteIdentifier($driver, $table);

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                DB::connection($connection)->statement("TRUNCATE TABLE {$quotedTable}");
                break;
            case 'pgsql':
                DB::connection($connection)->statement("TRUNCATE TABLE {$quotedTable} RESTART IDENTITY CASCADE");
                break;
            default:
                DB::connection($connection)->table($table)->delete();

                if ($driver === 'sqlite') {
                    try {
                        DB::connection($connection)->table('sqlite_sequence')->where('name', $table)->delete();
                    } catch (\Throwable) {
                        // sqlite_sequence may not exist if no AUTOINCREMENT tables were created.
                    }
                }
                break;
        }
    }

    private function quoteIdentifier(string $driver, string $identifier): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => '`' . str_replace('`', '``', $identifier) . '`',
            default => '"' . str_replace('"', '""', $identifier) . '"',
        };
    }

    private function decodePayload(string $json): array
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Backup file is not valid JSON.');
        }

        return $decoded;
    }

    private function assertSafeFilename(string $filename): string
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            throw new RuntimeException('Invalid backup filename.');
        }

        return $filename;
    }

    private function shouldSkipTable(string $driver, string $table): bool
    {
        if ($driver === 'sqlite' && str_starts_with($table, 'sqlite_')) {
            return true;
        }

        return false;
    }

    private function summarizeBackupFile(string $path): array
    {
        $defaults = [
            'backup_version' => null,
            'app_name' => null,
            'generated_at_iso' => null,
            'generated_at_human' => null,
            'central_driver' => null,
            'central_table_count' => null,
            'central_row_count' => null,
            'standalone_tenant_database' => null,
            'standalone_tenant_table_count' => null,
            'standalone_tenant_row_count' => null,
            'tenant_count' => null,
            'tenant_table_count' => null,
            'tenant_row_count' => null,
            'summary_label' => 'Unknown snapshot content',
            'parse_error' => true,
        ];

        try {
            $json = Storage::disk('local')->get($path);
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($payload)) {
                return $defaults;
            }

            $generatedAt = data_get($payload, 'meta.generated_at');
            $generatedAtTimestamp = is_string($generatedAt) ? strtotime($generatedAt) : false;

            $centralTables = data_get($payload, 'central.tables', []);
            if (!is_array($centralTables)) {
                $centralTables = [];
            }

            $centralTableCount = (int) data_get($payload, 'central.table_count', count($centralTables));
            $centralRowCount = $this->sumTableRows($centralTables);

            $standaloneTenantTables = data_get($payload, 'standalone_tenant.data.tables', []);
            if (!is_array($standaloneTenantTables)) {
                $standaloneTenantTables = [];
            }
            $standaloneTenantDatabase = data_get($payload, 'standalone_tenant.database_name');
            $standaloneTenantTableCount = null;
            $standaloneTenantRowCount = null;

            if (is_string($standaloneTenantDatabase) && trim($standaloneTenantDatabase) !== '') {
                $standaloneTenantTableCount = (int) data_get($payload, 'standalone_tenant.data.table_count', count($standaloneTenantTables));
                $standaloneTenantRowCount = $this->sumTableRows($standaloneTenantTables);
            }

            $tenantBackups = data_get($payload, 'tenants', []);
            if (!is_array($tenantBackups)) {
                $tenantBackups = [];
            }

            $tenantCount = 0;
            $tenantTableCount = 0;
            $tenantRowCount = 0;

            foreach ($tenantBackups as $tenantBackup) {
                if (!is_array($tenantBackup)) {
                    continue;
                }

                $tenantCount++;

                $tenantTables = data_get($tenantBackup, 'data.tables', []);
                if (!is_array($tenantTables)) {
                    $tenantTables = [];
                }

                $tenantTableCount += (int) data_get($tenantBackup, 'data.table_count', count($tenantTables));
                $tenantRowCount += $this->sumTableRows($tenantTables);
            }

            $summaryLabel = 'Central + ' . $tenantCount . ' tenant database' . ($tenantCount === 1 ? '' : 's');

            if (is_string($standaloneTenantDatabase) && trim($standaloneTenantDatabase) !== '') {
                $summaryLabel .= ' + standalone DB (' . trim($standaloneTenantDatabase) . ')';
            }

            return [
                'backup_version' => data_get($payload, 'meta.version'),
                'app_name' => data_get($payload, 'meta.app_name', config('app.name')),
                'generated_at_iso' => $generatedAtTimestamp ? date(DATE_ATOM, $generatedAtTimestamp) : null,
                'generated_at_human' => $generatedAtTimestamp ? date('M d, Y h:i A', $generatedAtTimestamp) : null,
                'central_driver' => data_get($payload, 'meta.central_driver', data_get($payload, 'central.driver')),
                'central_table_count' => $centralTableCount,
                'central_row_count' => $centralRowCount,
                'standalone_tenant_database' => is_string($standaloneTenantDatabase) ? trim($standaloneTenantDatabase) : null,
                'standalone_tenant_table_count' => $standaloneTenantTableCount,
                'standalone_tenant_row_count' => $standaloneTenantRowCount,
                'tenant_count' => $tenantCount,
                'tenant_table_count' => $tenantTableCount,
                'tenant_row_count' => $tenantRowCount,
                'summary_label' => $summaryLabel,
                'parse_error' => false,
            ];
        } catch (\Throwable) {
            return $defaults;
        }
    }

    private function sumTableRows(array $tables): int
    {
        $count = 0;

        foreach ($tables as $rows) {
            if (is_array($rows)) {
                $count += count($rows);
            }
        }

        return $count;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $size = $bytes / 1024;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 2) . ' ' . $units[$unitIndex];
    }
}
