# Tenancy for Laravel Integration Guide

## Overview

This project integrates **Tenancy for Laravel** v2 with a custom multi-tenancy implementation. The integration uses a "shallow" approach that:

- ✅ Keeps your proven, production-tested custom middleware
- ✅ Adds Tenancy framework support for future features
- ✅ Provides Tenancy-compatible helpers and facades
- ✅ Maintains 100% backward compatibility with existing code

## Architecture

### What We Have

**Custom Multi-Tenancy (Existing)**
- 4 custom middleware files for tenant resolution and access control
- Domain/subdomain-based tenant identification
- Session-based tenant context storage
- Role-based access control (6 tenant roles)
- Global query scoping via BelongsToTenant trait

**Tenancy for Laravel (New)**
- Database storage driver for tenant data
- Configuration framework
- Domains table for future expansion
- Compatible helper functions and facades

### How They Work Together

```
HTTP Request
    ↓
ResolveTenantFromDomain (custom middleware)
    ↓
Sets app('current_tenant') + session('current_tenant_id')
    ↓
Your application code
    ↓
TenancyManager / tenant() / tenancy() helpers
    (Read from session/container)
    ↓
BelongsToTenant trait auto-scopes queries
    ↓
Tenancy database storage (future use)
```

## Usage Guide

### Accessing Current Tenant

**Via Helper Function**
```php
// Get current tenant instance
$tenant = tenant();

// Get specific value
$tenantName = tenant('name');
$tenantId = tenant('id');

// With default value
$plan = tenant('plan_id', 'free');
```

**Via Facade/Service**
```php
use App\Facades\Tenancy;

$tenant = Tenancy::current();
$tenantId = Tenancy::getId();
$plan = Tenancy::get('plan_id');
```

**Direct from Session**
```php
$tenantId = session('current_tenant_id');
```

### Creating Models in Tenant Context

Models with `BelongsToTenant` trait auto-set `tenant_id`:

```php
// Automatically sets tenant_id from current context
$incident = Incident::create([
    'title' => 'Report from resident',
    'description' => 'Something happened',
    'status' => Incident::STATUS_OPEN,
    // tenant_id is automatically set
]);
```

###Querying Tenant Data

All queries on tenant-scoped models are automatically filtered:

```php
// Only returns incidents for current tenant
$incidents = Incident::all();

// Can still use where clauses
$openIncidents = Incident::where('status', 'open')->get();

// Bypass scoping for super admin
$allIncidents = Incident::withoutGlobalScope('tenant')->get();
```

### Running Code in Tenant Context (Non-HTTP)

For queued jobs, console commands, or background tasks:

```php
use App\Facades\Tenancy;

$tenant = Tenant::find($tenantId);

// Execute within tenant context
Tenancy::run($tenant, function (Tenant $tenant) {
    $newIncident = Incident::create([...]);
    // Queue a job, etc.
});

// Manual control
Tenancy::initialize($tenant);
try {
    // Your code here
} finally {
    Tenancy::end();
}
```

## Configuration

### `config/tenancy.php`

Key settings:

```php
'storage_driver' => 'db', // Uses database storage
'database' => [
    'based_on' => 'sqlite', // Database type
    'prefix' => 'tenant_',
    'suffix' => '.sqlite',
],
'tenant_model' => App\Models\Tenant::class,
```

Your custom columns are automatically handled:
- `plan_id`
- `name`
- `slug`
- `barangay`
- `address`
- `contact_phone`
- `is_active`

### Environment Variables

Add to `.env` as needed:

```env
TENANCY_STORAGE_DRIVER=db
TENANCY_DATABASE_BASED_ON=sqlite
TENANCY_DATABASE_PREFIX=tenant_
TENANCY_DATABASE_SUFFIX=.sqlite
```

## File Structure

```
app/
  ├── Facades/
  │   └── Tenancy.php              # Facade for TenancyManager
  ├── Services/
  │   └── TenancyManager.php       # Main tenancy service class
  ├── Helpers/
  │   └── tenancy.php              # Helper functions (tenant(), tenancy())
  ├── Providers/
  │   └── TenancyServiceProvider.php # Registers services & helpers
  └── Models/
      ├── Tenant.php               # Updated with Tenancy integration
      └── Traits/
          └── BelongsToTenant.php   # Global scoping trait
          
config/
  └── tenancy.php                  # Tenancy framework config

database/
  ├── migrations/
  │   ├── *_create_tenants_table.php
  │   ├── *_create_domains_table.php  # NEW - for Tenancy
  │   └── ...
  └── factories/
      ├── TenantFactory.php        # NEW - for testing
      └── IncidentFactory.php      # NEW - for testing
```

## Models with Tenancy Support

The following models use the `BelongsToTenant` trait for automatic scoping:

- ✅ `Incident` - Blotter incidents
- ✅ `PatrolLog` - Patrol records
- ✅ `Mediation` - Mediation cases
- ✅ `BlotterRequest` - Requests

Models without tenants:
- ✅ `User` - Global users (via pivot with Tenant)
- ✅ `Plan` - Global plans (shared across tenants)
- ✅ `IncidentAttachment` - Scoped via Incident

## Middleware Chain

Your existing middleware chain is preserved:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\ResolveTenantFromDomain::class,
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
    $middleware->alias([
        'tenant' => \App\Http\Middleware\SetTenantFromSession::class,
        'tenant.ensure' => \App\Http\Middleware\EnsureUserBelongsToTenant::class,
        'tenant.role' => \App\Http\Middleware\EnsureTenantRole::class,
        'super_admin' => \App\Http\Middleware\SuperAdminOnly::class,
    ]);
})
```

## Testing with Tenancy

Create test tenants and verify isolation:

```php
use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Incident;

class TenantIsolationTest extends TestCase
{
    public function test_queries_respect_tenant_scoping()
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Barangay A']);
        $tenant2 = Tenant::factory()->create(['name' => 'Barangay B']);
        
        $incident1 = Incident::factory()->create(['tenant_id' => $tenant1->id]);
        $incident2 = Incident::factory()->create(['tenant_id' => $tenant2->id]);
        
        // Set context to tenant1
        session(['current_tenant_id' => $tenant1->id]);
        
        // Only see tenant1's incident
        $this->assertCount(1, Incident::all());
        $this->assertEquals($incident1->id, Incident::first()->id);
    }
}
```

## Migration Path

If you want to migrate to Tenancy's full identification system in the future:

1. Create custom `IdentificationDriver` implementing `Stancl\Tenancy\Contracts\IdentificationDriver`
2. Register in `config/tenancy.php` under `identification_drivers`
3. Use `Tenancy::tenant()` to access current tenant
4. Remove custom middleware one by one

This is optional and can be done incrementally.

## Troubleshooting

### Tenant Not Found

**Problem**: `tenant()` returns null

**Solution**:
- Verify middleware is registered in `bootstrap/app.php`
- Check that `app('current_tenant')` or `session('current_tenant_id')` is set
- Ensure tenant is `is_active = true`

### Tenant Data Not Scoped

**Problem**: Queries return all data regardless of tenant

**Solution**:
- Verify model uses `use BelongsToTenant;` trait
- Check that `tenant_id` column exists on the table
- Ensure migration includes `$table->foreignId('tenant_id')`

### Models Not Using Factory

**Problem**: `Tenant::factory()->create()` throws "Call to undefined method"

**Solution**:
- Model must use `use HasFactory;` trait
- Ensure factory file exists in `database/factories/`
- Run `composer dump-autoload`

## Performance Considerations

1. **Global Scopes**: Automatically added to all queries on tenant-scoped models
   - Adds minimal overhead (`WHERE tenant_id = ?`)
   - Indexed on `tenant_id` column

2. **Session Storage**: Tenant ID stored in session
   - Avoids repeated database lookups
   - Survives across requests

3. **Helper Functions**: Simple wrappers around session/container
   - Zero performance impact
   - Consistent API across codebase

## Best Practices

1. **Always access via helper**
   ```php
   // Good
   $tenant = tenant();
   
   // Avoid
   $tenantId = session('current_tenant_id');
   ```

2. **Use tenant() in controllers**
   ```php
   public function show($id)
   {
       $incident = Incident::findOrFail($id);
       return response()->json($incident);
   }
   ```

3. **Use Tenancy::run() for background jobs**
   ```php
   class GenerateReport
   {
       public function handle()
       {
           Tenancy::run($this->tenant, function () {
               // All queries here are scoped to tenant
           });
       }
   }
   ```

4. **Test with tenants**
   ```php
   // Recommended
   $this->actingAs($user, 'web');
   $response = $this->get('/incidents');
   
   // vs. direct access
   // Avoid direct model queries in tests
   ```

## Next Steps

- ✅ Integration complete and working
- 📦 Database storage configured (ready for multi-database tenancy)
- 📋 Configuration in place
- 🧪 Test structure provided
- 📚 Documentation complete

### Future Enhancements

1. **Multi-Database Tenancy**: Configure per-tenant databases
   - Set `database.based_on` to separate DB per tenant
   - Use `TenantDatabaseManager` for automatic DB creation

2. **Custom Identification**: Replace custom middleware with Tenancy's identification drivers

3. **Tenancy Events**: Hook into `tenancy:` events for custom logic

4. **Filesystem Tenancy**: Isolate file uploads per tenant

## Support & References

- **Tenancy for Laravel Docs**: https://tenancyforlaravel.com/docs/v2
- **Laravel Documentation**: https://laravel.com/docs
- **Spatie Laravel Permissions**: https://spatie.be/docs/laravel-permission

## Summary

You now have:
- ✅ Tenancy framework installed and configured
- ✅ Database storage ready
- ✅ Shallow integration that preserves your middleware
- ✅ Helper functions and facades for Tenancy-compatible code
- ✅ Automatic query scoping on tenant-aware models
- ✅ Easy-to-use `tenant()`, `tenancy()`, and `Tenancy` APIs
- ✅ Backward compatibility maintained 100%
- ✅ Path forward for deeper Tenancy integration if needed

Your custom middleware continues to work as-is, while your code can now also use modern Tenancy APIs for accessing tenant data.
