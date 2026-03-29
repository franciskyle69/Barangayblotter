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

    public function createBackup(): array
    {
        $payload = $this->buildSnapshot();

        $filename = 'full-backup-' . now()->format('Ymd_His') . '.json';
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

    private function buildSnapshot(): array
    {
        $centralConnection = config('tenancy.central_connection', 'central');

        $snapshot = [
            'meta' => [
                'version' => 1,
                'generated_at' => now()->toIso8601String(),
                'app_name' => config('app.name'),
                'central_connection' => $centralConnection,
                'central_driver' => $this->driverForConnection($centralConnection),
            ],
            'central' => $this->dumpConnection($centralConnection),
            'tenants' => [],
        ];

        $tenants = Tenant::query()
            ->select(['id', 'name', 'database_name'])
            ->orderBy('id')
            ->get();

        foreach ($tenants as $tenant) {
            if (!$tenant->database_name) {
                continue;
            }

            $tenantConnection = $this->configureTenantConnection('backup_tenant', $tenant->database_name);

            $snapshot['tenants'][] = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'database_name' => $tenant->database_name,
                'driver' => $this->driverForConnection($tenantConnection),
                'data' => $this->dumpConnection($tenantConnection),
            ];
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

        $tenantBackups = data_get($payload, 'tenants', []);
        if (!is_array($tenantBackups)) {
            return;
        }

        foreach ($tenantBackups as $tenantBackup) {
            if (!is_array($tenantBackup)) {
                continue;
            }

            $tenantTables = data_get($tenantBackup, 'data.tables');
            $databaseName = data_get($tenantBackup, 'database_name');

            if (!is_array($tenantTables) || !is_string($databaseName) || trim($databaseName) === '') {
                continue;
            }

            $tenantConnection = $this->configureTenantConnection('backup_tenant_restore', $databaseName);
            $this->restoreConnectionData($tenantConnection, $tenantTables);
        }
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

            return [
                'backup_version' => data_get($payload, 'meta.version'),
                'app_name' => data_get($payload, 'meta.app_name', config('app.name')),
                'generated_at_iso' => $generatedAtTimestamp ? date(DATE_ATOM, $generatedAtTimestamp) : null,
                'generated_at_human' => $generatedAtTimestamp ? date('M d, Y h:i A', $generatedAtTimestamp) : null,
                'central_driver' => data_get($payload, 'meta.central_driver', data_get($payload, 'central.driver')),
                'central_table_count' => $centralTableCount,
                'central_row_count' => $centralRowCount,
                'tenant_count' => $tenantCount,
                'tenant_table_count' => $tenantTableCount,
                'tenant_row_count' => $tenantRowCount,
                'summary_label' => 'Central + ' . $tenantCount . ' tenant database' . ($tenantCount === 1 ? '' : 's'),
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
