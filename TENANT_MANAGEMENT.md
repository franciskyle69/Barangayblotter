# 🏘️ Tenant Management Guide - Seeding & Deletion

## Overview

This guide explains how to manage barangay (tenant) creation and deletion in your Barangay Blotter system.

---

## ✋ Disabling Tenant Seeding

### What Changed?

**Before**: Running `php artisan migrate:fresh --seed` would automatically create 5 sample tenants (Casisang, Sumpong, San Jose, Kalasungay, Caburacanan).

**Now**: The `TenantSeeder` has been **disabled**. Database seeding only creates:
- ✅ Plans (Basic, Standard, Premium)
- ✅ Super Admin user
- ❌ Tenants (disabled - create manually instead)

### Why Disable Seeding?

1. **Production Control**: No accidental test data in production
2. **Manual Creation**: Create only the barangays you actually need
3. **Clean Data**: Start with a clean slate
4. **Flexibility**: Add tenants at your own pace

### How It Works

**File Modified**: `database/seeders/DatabaseSeeder.php`

```php
public function run(): void
{
    $this->call([
        PlanSeeder::class,
        // TenantSeeder::class, // ← DISABLED
        UserSeeder::class,
    ]);
}
```

---

## 🚀 Creating Tenants (Barangays)

### Method 1: Via Web Interface (Recommended)

1. **Login as Super Admin**
   - URL: `http://127.0.0.1:8000/super`
   - Email: `city@malaybalay.test`
   - Password: `password`

2. **Navigate to Tenants**
   - Click "Barangays" in the sidebar
   - Click "Create New Barangay" button

3. **Fill in the Form**
   - **Name**: "Casisang" (display name)
   - **Slug**: "casisang" (URL-friendly, lowercase, no spaces)
   - **Subdomain**: "casisang" (optional, for subdomain access)
   - **Barangay**: "South Highway"
   - **Address**: Full address
   - **Contact Phone**: Phone number (optional)
   - **Plan**: Select Basic/Standard/Premium
   - **Status**: Active/Inactive

4. **Click Save**

### Method 2: Via Artisan Command

Create a single tenant:

```bash
php artisan tenant:create
```

The command will:
1. Show you a list of existing tenants
2. Ask for the tenant slug
3. Ask for all required information
4. Create the tenant

**Example**:
```bash
$ php artisan tenant:create

📋 Available Tenants:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

(No tenants yet)

Enter the slug of the tenant: casisang
Enter the name: Casisang
Enter the subdomain: casisang
...
✅ Tenant created successfully!
```

### Method 3: Restore Sample Data (Development Only)

If you want the 5 sample tenants back **for development only**:

```bash
php artisan db:seed --class=TenantSeeder
```

This will create:
1. Casisang
2. Sumpong
3. San Jose
4. Kalasungay
5. Caburacanan

⚠️ **Warning**: Only use this in development/testing, not in production!

---

## 🗑️ Deleting Tenants (Barangays)

### Method 1: Via Web Interface (Recommended)

1. **Login as Super Admin**
   - URL: `http://127.0.0.1:8000/super`

2. **Navigate to Tenants**
   - Click "Barangays" in the sidebar

3. **Find the Barangay to Delete**
   - Locate it in the table
   - Click the "Edit" button

4. **Delete Barangay**
   - Scroll to the bottom of the form
   - Click "Delete Barangay" button (red)

5. **Confirm Deletion**
   - Read the warning carefully ⚠️
   - See the list of data that will be deleted:
     - Incidents
     - Mediations
     - Patrol Logs
     - Blotter Requests
     - User Associations
   - Type the barangay name to confirm
   - Click "Confirm Deletion"

### Method 2: Via Artisan Command

Delete a tenant from the command line:

```bash
php artisan tenant:delete
```

**Features**:
- Shows list of available tenants
- Displays all data that will be deleted
- Requires name confirmation
- Deletes in a database transaction (all or nothing)

**Example**:
```bash
$ php artisan tenant:delete

📋 Available Tenants:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
| ID | Name       | Slug       | Barangay      | Status    |
|----|------------|------------|---------------|-----------|
| 1  | Casisang   | casisang   | South Highway | ✓ Active  |
| 2  | Sumpong    | sumpong    | North Highway | ✓ Active  |

Enter the slug of the tenant to delete: casisang

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TENANT DELETION WARNING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Tenant Information:
  ID: 1
  Name: Casisang
  Slug: casisang
  Barangay: South Highway
  Status: Active

This action will DELETE the following data:
  • 45 Incident(s)
  • 12 Mediation(s)
  • 8 Patrol Log(s)
  • 3 Blotter Request(s)
  • 2 User Association(s)

This action is PERMANENT and CANNOT BE UNDONE

Are you sure you want to delete this tenant and all its data? (yes/no) [no]:
> yes

Type the tenant name "Casisang" to confirm:
> Casisang

Deleting tenant and all associated data...
  ✓ Deleted incidents
  ✓ Deleted mediations
  ✓ Deleted patrol logs
  ✓ Deleted blotter requests
  ✓ Removed user associations
  ✓ Deleted tenant

✅ Tenant 'Casisang' has been successfully deleted!
```

**With Force Flag** (skip confirmation):

```bash
php artisan tenant:delete casisang --force
```

This skips the confirmation prompts (use with caution!).

---

## 📊 What Gets Deleted?

When you delete a tenant, **ALL** associated data is permanently removed:

```
Tenant
├── Incidents (all associated incidents)
│   └── Incident Attachments (photos, files)
├── Mediations (all mediation records)
├── Patrol Logs (all patrol logs)
├── Blotter Requests (all blotter request records)
└── User Associations (users assigned to this barangay)
```

**What is NOT deleted**:
- ✓ User accounts (they can be reassigned to other barangays)
- ✓ Plans (they can be used by other tenants)
- ✓ The Plan associated with the tenant

---

## 🔄 Workflow Example

### Scenario: Manage a Development Environment

```bash
# 1. Start fresh
php artisan migrate:fresh --seed

# 2. Create a test barangay
php artisan tenant:create
# Slug: test-barangay
# Name: Test Barangay
# Plan: Basic

# 3. Do your testing...

# 4. Delete it when done
php artisan tenant:delete test-barangay --force

# 5. Create another barangay
php artisan tenant:create
```

### Scenario: Manage Production

```bash
# 1. Seed initial data (no tenants)
php artisan migrate --seed

# 2. Create live barangays via web interface
# (Go to http://your-domain/super/tenants/create)

# 3. To remove a barangay:
# - Go to Super Admin → Barangays
# - Click Edit on the barangay
# - Click Delete Barangay button
# - Confirm with barangay name
```

---

## 🛡️ Safety Features

### Web Interface Protection

When deleting via web interface:
1. ✅ Shows confirmation page with all warnings
2. ✅ Displays count of affected records
3. ✅ Requires typing barangay name
4. ✅ Displays success message after deletion

### Command Line Protection

When deleting via CLI:
1. ✅ Shows list of tenants for reference
2. ✅ Displays all tenant information
3. ✅ Shows count of related records
4. ✅ Interactive confirmation prompt
5. ✅ Optional `--force` flag for automation
6. ✅ Database transaction (atomic - all or nothing)

---

## 📝 Available Artisan Commands

### Tenant Management Commands

```bash
# List all tenants
php artisan tenant:list

# Create a new tenant
php artisan tenant:create

# Delete a tenant
php artisan tenant:delete {slug?}

# Delete with force flag (no confirmation)
php artisan tenant:delete casisang --force

# Toggle tenant active status
php artisan tenant:toggle casisang

# Show tenant details
php artisan tenant:info casisang
```

---

## 🔧 Technical Details

### Files Modified

1. **`database/seeders/DatabaseSeeder.php`**
   - Commented out `TenantSeeder::class`

2. **`app/Console/Commands/DeleteTenantCommand.php`** (NEW)
   - Handles CLI tenant deletion
   - Provides user-friendly interface
   - Includes confirmation and safety checks

3. **`app/Http/Controllers/SuperAdminController.php`**
   - Added `deleteTenant()` method
   - Validates confirmation
   - Deletes tenant in transaction

4. **`routes/web.php`**
   - Added `DELETE /super/tenants/{tenant}` route

### Database Relationships

The deletion is safe because:
- All related records have `tenant_id` foreign keys
- Cascade delete is configured in migrations
- Transaction ensures all-or-nothing operation

---

## ❓ FAQ

**Q: Can I recover a deleted tenant?**
A: No, deletion is permanent. Ensure you have database backups if needed.

**Q: What if I want to temporarily disable a tenant instead of deleting?**
A: Use the toggle button in the web interface instead. This sets `is_active = false` without deleting data.

**Q: Can a regular barangay user delete their barangay?**
A: No, only Super Admins can delete tenants.

**Q: What happens to users assigned to a deleted barangay?**
A: Users are detached from the barangay but their accounts remain. They can be assigned to other barangays.

**Q: Can I delete a barangay if it has incidents?**
A: Yes, the deletion cascades - all incidents and related data are deleted too. However, the system will warn you about this.

**Q: How do I know how many records will be deleted?**
A: The web interface and CLI command both show counts of all related records before asking for confirmation.

---

## 🚀 Quick Reference

| Task | Command/Action | Notes |
|------|--|--|
| Create tenant | Web: Click "Create Barangay" or `php artisan tenant:create` | Interactive |
| Delete tenant | Web: Click "Delete" or `php artisan tenant:delete` | Requires confirmation |
| View tenants | Web: Go to Super Admin → Barangays | Shows all tenants |
| Deactivate tenant | Web: Click toggle | Data preserved |
| Reactivate tenant | Web: Click toggle | Restores access |
| List tenants (CLI) | `php artisan tenant:list` | Shows all tenants |
| Force delete (CLI) | `php artisan tenant:delete slug --force` | Skips confirmation |

---

## 📞 Support

If you encounter issues:

1. **Web Interface Errors**: Check the browser console (F12)
2. **CLI Errors**: Run with `-v` flag for verbose output
3. **Database Issues**: Ensure migrations are up to date (`php artisan migrate`)
4. **Permissions**: Ensure you're logged in as Super Admin

---

**Last Updated**: March 25, 2026
**System Version**: 1.0
