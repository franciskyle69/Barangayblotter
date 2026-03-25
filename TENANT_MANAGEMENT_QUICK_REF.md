# 🔧 Tenant Management - Quick Dev Reference

## TL;DR

| Need | Command/Action |
|------|--|
| **Disable seeding** | ✅ DONE - Commented out `TenantSeeder` |
| **Create tenant** | Web: `/super/tenants/create` OR `php artisan tenant:create` |
| **Delete tenant** | Web: `/super/tenants/{id}/edit` → Delete button OR `php artisan tenant:delete` |
| **Force delete** | `php artisan tenant:delete slug --force` |
| **View all tenants** | Web: `/super/tenants` OR `php artisan tenant:list` |

---

## Changes Summary

### 1️⃣ Disabled Tenant Seeding

**File**: `database/seeders/DatabaseSeeder.php`

```php
// ❌ Before
$this->call([
    PlanSeeder::class,
    TenantSeeder::class,  // Creates 5 sample tenants
    UserSeeder::class,
]);

// ✅ After
$this->call([
    PlanSeeder::class,
    // TenantSeeder::class,  // DISABLED
    UserSeeder::class,
]);
```

**Result**: `php artisan migrate:fresh --seed` no longer creates sample tenants.

---

### 2️⃣ New Deletion Command

**File**: `app/Console/Commands/DeleteTenantCommand.php` (NEW)

```bash
# Show available tenants and delete one
php artisan tenant:delete

# Delete specific tenant (no slug: shows list)
php artisan tenant:delete casisang

# Delete without confirmation (for scripts)
php artisan tenant:delete casisang --force
```

**Features**:
- ✅ Lists all tenants
- ✅ Shows count of data to be deleted
- ✅ Requires name confirmation
- ✅ Deletes in database transaction
- ✅ User-friendly error messages

---

### 3️⃣ Web Interface Deletion

**File**: `app/Http/Controllers/SuperAdminController.php`

Added new method:
```php
public function deleteTenant(Request $request, Tenant $tenant): RedirectResponse
```

**Flow**:
1. Admin navigates to `/super/tenants/{id}/edit`
2. Clicks "Delete Barangay" button
3. Sees confirmation page with warnings
4. Types barangay name to confirm
5. Clicks "Confirm Deletion"
6. Tenant and all related data deleted

---

### 4️⃣ New Route

**File**: `routes/web.php`

```php
Route::delete('tenants/{tenant}', [SuperAdminController::class, 'deleteTenant'])
    ->name('tenants.destroy');
```

---

## What Gets Deleted?

When you delete a tenant:

```
✗ Tenant
  ✗ incidents
  ✗ mediations
  ✗ patrol_logs
  ✗ blotter_requests
  ✗ tenant_user (associations)

✓ User accounts (preserved)
✓ Plans (preserved)
```

---

## Usage Examples

### Scenario 1: Create and Delete Test Data

```bash
# 1. Fresh database
php artisan migrate:fresh --seed

# 2. Create test tenant
php artisan tenant:create
# Enter: casisang, Casisang, casisang, etc.

# 3. Do testing...

# 4. Clean up
php artisan tenant:delete casisang --force

# 5. Create another
php artisan tenant:create
```

### Scenario 2: Production Workflow

```bash
# Initial setup
php artisan migrate --seed

# Add real barangays
# → Use web interface at /super/tenants/create

# Remove a barangay
# → Use web interface at /super/tenants/{id}/edit
# → Click Delete Barangay

# Or via CLI (careful!)
php artisan tenant:delete barangay-slug
```

---

## Safety Checks

### Web Interface
- Shows full confirmation page
- Displays all affected records
- Requires typing barangay name
- Success message after deletion

### CLI
- Lists all tenants before asking
- Shows all information about target tenant
- Shows count of related records
- Requires typed confirmation (unless --force)
- Database transaction (atomic)

---

## API Reference

### CreateTenant

**Web**:
```
GET  /super/tenants/create      → Show form
POST /super/tenants             → Store tenant
```

**CLI**:
```bash
php artisan tenant:create
```

### UpdateTenant

**Web**:
```
GET /super/tenants/{tenant}/edit    → Show edit form
PUT /super/tenants/{tenant}         → Update tenant
```

### ToggleTenant

**Web**:
```
POST /super/tenants/{tenant}/toggle → Activate/deactivate
```

### DeleteTenant

**Web**:
```
DELETE /super/tenants/{tenant}      → Delete tenant
GET    /super/tenants/{tenant}/edit → Form with delete button
POST   /super/tenants/{tenant}      → Confirmation form submission
```

**CLI**:
```bash
php artisan tenant:delete {slug?} {--force}
```

---

## Testing Checklist

- [ ] Run `php artisan migrate:fresh --seed` - no tenants created
- [ ] Create tenant via web interface - works
- [ ] Create tenant via CLI - works
- [ ] List tenants - shows all
- [ ] Delete tenant via web interface - works with confirmation
- [ ] Delete tenant via CLI - works with confirmation
- [ ] Force delete via CLI - works without confirmation
- [ ] Verify related data deleted
- [ ] Verify users are detached (not deleted)

---

## Troubleshooting

### "Tenant not found"
```bash
# Check available tenants
php artisan tenant:list
```

### "Confirmation name does not match"
```bash
# CLI: Type exact tenant name
# Web: Type exact barangay name
```

### Deletion fails silently
```bash
# Check database transactions
# Verify foreign key constraints
# Check app logs: storage/logs/
```

### Can't delete via web interface
```bash
# Verify Super Admin logged in
# Check routes are registered: php artisan route:list | grep tenant
# Check browser console (F12) for JS errors
```

---

## Files Modified

| File | Change | Lines |
|------|--------|-------|
| `database/seeders/DatabaseSeeder.php` | Comment out TenantSeeder | 1 line |
| `app/Console/Commands/DeleteTenantCommand.php` | NEW file | 150 lines |
| `app/Http/Controllers/SuperAdminController.php` | Add deleteTenant() | +40 lines |
| `routes/web.php` | Add DELETE route | +1 line |

---

## Version Info

- **Created**: March 25, 2026
- **Laravel**: 11.31
- **PHP**: 8.2+
- **Tenancy for Laravel**: v2.4.2

---

**Ready to use!** Start by reading `TENANT_MANAGEMENT.md` for the full guide.
