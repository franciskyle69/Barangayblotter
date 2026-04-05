<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class TenantDatabaseManager
{
    public function provisionTenantDatabase(Tenant $tenant): void
    {
        if (!$tenant->exists) {
            throw new RuntimeException('Tenant must be persisted before provisioning a database.');
        }

        $databaseName = $tenant->database_name ?: $this->generateDatabaseName($tenant->id);
        $this->assertSafeDatabaseName($databaseName);

        $created = false;

        try {
            $this->createPhysicalDatabase($databaseName);
            $created = true;

            $this->configureTenantConnection($databaseName);
            $this->ensureTenantSchema('tenant');

            if ($tenant->database_name !== $databaseName) {
                $tenant->database_name = $databaseName;
                $tenant->save();
            }
        } catch (Throwable $e) {
            if ($created) {
                $this->dropPhysicalDatabase($databaseName);
            }

            throw new RuntimeException('Failed to provision tenant database: ' . $e->getMessage(), 0, $e);
        }
    }

    public function activateTenantConnection(Tenant $tenant): void
    {
        if (!$tenant->database_name) {
            throw new RuntimeException('Tenant does not have an assigned database.');
        }

        $this->assertSafeDatabaseName($tenant->database_name);

        $this->configureTenantConnection($tenant->database_name);
        DB::purge('tenant');

        // Verify tenant connection before switching the default.
        DB::connection('tenant')->getPdo();

        DB::setDefaultConnection('tenant');
        app()->instance('tenant_connection_name', 'tenant');
    }

    public function resetToCentralConnection(): void
    {
        DB::setDefaultConnection($this->centralConnectionName());
        if (app()->bound('tenant_connection_name')) {
            app()->forgetInstance('tenant_connection_name');
        }
    }

    private function generateDatabaseName(int|string $tenantId): string
    {
        // Hash the tenant ID for obfuscation
        // Use first 12 chars of SHA256 hash for security while keeping it reasonably short
        $hash = substr(hash('sha256', (string) $tenantId), 0, 12);
        return 'tenant_' . $hash;
    }

    private function centralConnectionName(): string
    {
        return config('tenancy.central_connection', 'central');
    }

    private function centralConnectionConfig(): array
    {
        $central = $this->centralConnectionName();
        $config = config("database.connections.{$central}");

        if (!$config) {
            throw new RuntimeException("Central DB connection [{$central}] is not configured.");
        }

        return $config;
    }

    private function configureTenantConnection(string $databaseName): void
    {
        $base = $this->centralConnectionConfig();
        $driver = $base['driver'] ?? null;

        if (!$driver) {
            throw new RuntimeException('Central DB driver is missing.');
        }

        if ($driver === 'sqlite') {
            $path = $this->sqliteTenantDatabasePath($databaseName);
            $connection = array_merge($base, ['database' => $path]);
        } else {
            $connection = array_merge($base, ['database' => $databaseName]);
        }

        config(['database.connections.tenant' => $connection]);
    }

    private function createPhysicalDatabase(string $databaseName): void
    {
        $central = $this->centralConnectionName();
        $driver = $this->centralConnectionConfig()['driver'] ?? null;

        switch ($driver) {
            case 'sqlite':
                $path = $this->sqliteTenantDatabasePath($databaseName);
                $directory = dirname($path);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                if (!File::exists($path)) {
                    File::put($path, '');
                }
                break;

            case 'mysql':
            case 'mariadb':
                $quotedName = str_replace('`', '``', $databaseName);
                DB::connection($central)->statement("CREATE DATABASE IF NOT EXISTS `{$quotedName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                break;

            case 'pgsql':
                $exists = DB::connection($central)
                    ->table('pg_database')
                    ->where('datname', $databaseName)
                    ->exists();

                if (!$exists) {
                    $quotedName = str_replace('"', '""', $databaseName);
                    DB::connection($central)->statement("CREATE DATABASE \"{$quotedName}\"");
                }
                break;

            default:
                throw new RuntimeException("Unsupported central database driver [{$driver}] for tenant provisioning.");
        }
    }

    private function dropPhysicalDatabase(string $databaseName): void
    {
        $central = $this->centralConnectionName();
        $driver = $this->centralConnectionConfig()['driver'] ?? null;

        try {
            switch ($driver) {
                case 'sqlite':
                    $path = $this->sqliteTenantDatabasePath($databaseName);
                    if (File::exists($path)) {
                        File::delete($path);
                    }
                    break;

                case 'mysql':
                case 'mariadb':
                    $quotedName = str_replace('`', '``', $databaseName);
                    DB::connection($central)->statement("DROP DATABASE IF EXISTS `{$quotedName}`");
                    break;

                case 'pgsql':
                    $quotedName = str_replace('"', '""', $databaseName);
                    DB::connection($central)->statement("DROP DATABASE IF EXISTS \"{$quotedName}\"");
                    break;
            }
        } catch (Throwable) {
            // Cleanup is best effort; root exception is more important.
        }
    }

    private function sqliteTenantDatabasePath(string $databaseName): string
    {
        $filename = str_ends_with($databaseName, '.sqlite')
            ? $databaseName
            : $databaseName . '.sqlite';

        return database_path('tenants/' . $filename);
    }

    private function ensureTenantSchema(string $connection): void
    {
        $schema = Schema::connection($connection);

        if (!$schema->hasTable('incidents')) {
            $schema->create('incidents', function ($table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('blotter_number')->nullable();
                $table->string('incident_type');
                $table->text('description');
                $table->string('location')->nullable();
                $table->dateTime('incident_date');
                $table->string('complainant_name');
                $table->string('complainant_contact')->nullable();
                $table->string('complainant_address')->nullable();
                $table->unsignedBigInteger('complainant_user_id')->nullable();
                $table->string('respondent_name');
                $table->string('respondent_contact')->nullable();
                $table->string('respondent_address')->nullable();
                $table->string('status')->default('open');
                $table->unsignedBigInteger('reported_by_user_id')->nullable();
                $table->boolean('submitted_online')->default(false);
                $table->timestamps();
                $table->index(['tenant_id', 'incident_date']);
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!$schema->hasTable('incident_attachments')) {
            $schema->create('incident_attachments', function ($table) {
                $table->id();
                $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('mediations')) {
            $schema->create('mediations', function ($table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
                $table->unsignedBigInteger('mediator_user_id');
                $table->dateTime('scheduled_at');
                $table->string('status')->default('scheduled');
                $table->text('agreement_notes')->nullable();
                $table->text('settlement_terms')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('patrol_logs')) {
            $schema->create('patrol_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id');
                $table->date('patrol_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('area_patrolled')->nullable();
                $table->text('activities')->nullable();
                $table->text('incidents_observed')->nullable();
                $table->text('response_details')->nullable();
                $table->unsignedInteger('response_time_minutes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'patrol_date']);
            });
        }

        if (!$schema->hasTable('blotter_requests')) {
            $schema->create('blotter_requests', function ($table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
                $table->unsignedBigInteger('requested_by_user_id');
                $table->string('purpose')->nullable();
                $table->string('status')->default('pending');
                $table->unsignedBigInteger('admin_user_id')->nullable();
                $table->text('remarks')->nullable();
                $table->string('certificate_path')->nullable();
                $table->string('verification_code')->nullable();
                $table->timestamp('printed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function assertSafeDatabaseName(string $databaseName): void
    {
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $databaseName)) {
            throw new RuntimeException('Database name contains invalid characters.');
        }
    }
}
