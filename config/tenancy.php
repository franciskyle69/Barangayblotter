<?php

use Stancl\Tenancy\TenancyBootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\TenancyBootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\TenancyBootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\TenancyBootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\UUIDGenerator;

return [
    /*
    |--------------------------------------------------------------------------
    | Central Database Connection
    |--------------------------------------------------------------------------
    |
    | The master database connection that stores tenant metadata, users,
    | plans, and cross-tenant membership tables.
    |
    */
    'central_connection' => env('TENANCY_CENTRAL_CONNECTION', 'central'),

    /*
    |--------------------------------------------------------------------------
    | Tenancy Storage Driver
    |--------------------------------------------------------------------------
    |
    | Determines where tenant data is stored. Default: 'db' (database).
    | Other options: 'redis'
    |
    */
    'storage_driver' => env('TENANCY_STORAGE_DRIVER', 'db'),

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the selected storage driver.
    |
    */
    'storage' => [
        'db' => [
            'connection' => null,
            'table_names' => [
                'tenants' => 'tenants',
                'domains' => 'domains',
            ],
            'custom_columns' => [
                'plan_id',
                'name',
                'slug',
                'barangay',
                'address',
                'contact_phone',
                'is_active',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant & Domain Models
    |--------------------------------------------------------------------------
    |
    | The Eloquent models used for tenant and domain storage.
    |
    */
    'tenant_model' => App\Models\Tenant::class,
    'domain_model' => Stancl\Tenancy\Models\Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | For multi-database tenancy, configure database creation and prefixing.
    |
    */
    'database' => [
        'based_on' => env('TENANCY_DATABASE_BASED_ON', 'sqlite'),
        'prefix' => env('TENANCY_DATABASE_PREFIX', 'tenant_'),
        'suffix' => env('TENANCY_DATABASE_SUFFIX', '.sqlite'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure tenant-aware caching using cache tags.
    |
    */
    'cache' => [
        'tag_base' => env('TENANCY_CACHE_TAG_BASE', 'tenant_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Redis connections to be tenant-aware.
    |
    */
    'redis' => [
        'prefix_base' => env('TENANCY_REDIS_PREFIX_BASE', 'tenant_'),
        'prefixed_connections' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which disks should be tenant-aware.
    |
    */
    'filesystem' => [
        'suffix_base' => env('TENANCY_FILESYSTEM_SUFFIX_BASE', 'tenant_'),
        'disks' => ['local'],
        'root_override' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Parameters
    |--------------------------------------------------------------------------
    |
    | Parameters passed to migrations when creating a tenant database.
    |
    */
    'migration_parameters' => [
        '--step' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy Bootstrappers
    |--------------------------------------------------------------------------
    |
    | Classes that initialize tenant-aware features when tenancy is activated.
    | Executed in the order defined.
    |
    */
    'bootstrappers' => [
        'database' => DatabaseTenancyBootstrapper::class,
        'cache' => CacheTenancyBootstrapper::class,
        'filesystem' => FilesystemTenancyBootstrapper::class,
        'queue' => QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Managers
    |--------------------------------------------------------------------------
    |
    | Maps database drivers to their respective managers for tenant DB creation.
    |
    */
    'database_managers' => [
        'sqlite' => \Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
        'mysql' => \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        'pgsql' => \Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Manager Connections
    |--------------------------------------------------------------------------
    |
    | Specifies which database connection each database manager should use.
    |
    */
    'database_manager_connections' => [
        'sqlite' => 'sqlite',
        'mysql' => 'mysql',
        'pgsql' => 'pgsql',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Optional features that enhance tenancy functionality.
    |
    */
    'features' => [
        // Stancl\Tenancy\Features\TenantConfig::class,
        // Stancl\Tenancy\Features\UserImpersonation::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Unique ID Generator
    |--------------------------------------------------------------------------
    |
    | Class used to generate unique tenant IDs.
    |
    */
    'unique_id_generator' => UUIDGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Home URL
    |--------------------------------------------------------------------------
    |
    | URL to redirect to when accessing tenant routes from central domain.
    |
    */
    'home_url' => '/',

    /*
    |--------------------------------------------------------------------------
    | Queue & Migration Settings
    |--------------------------------------------------------------------------
    |
    */
    'queue_database_creation' => env('TENANCY_QUEUE_DATABASE_CREATION', false),
    'queue_database_deletion' => env('TENANCY_QUEUE_DATABASE_DELETION', false),
    'migrate_after_creation' => env('TENANCY_MIGRATE_AFTER_CREATION', false),
    'seed_after_migration' => env('TENANCY_SEED_AFTER_MIGRATION', false),

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],

    'delete_database_after_tenant_deletion' => env('TENANCY_DELETE_DATABASE_AFTER_TENANT_DELETION', false),

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    |
    */
    'tenant_route_namespace' => 'App\Http\Controllers',
    'exempt_domains' => [
        env('TENANCY_EXEMPT_DOMAINS', 'admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware aliases for tenant identification and access control.
    |
    */
    'http_middleware' => [
        'identify_tenant' => Stancl\Tenancy\Middleware\IdentifyTenant::class,
        'prevent_access_from_tenant_domains' => Stancl\Tenancy\Middleware\PreventAccessFromTenantDomains::class,
    ],
];
