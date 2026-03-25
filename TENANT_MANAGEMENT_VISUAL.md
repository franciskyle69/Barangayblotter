# 🎨 Tenant Management - Visual Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                      BARANGAY BLOTTER SYSTEM                        │
└─────────────────────────────────────────────────────────────────────┘

                    ┌──────────────────────┐
                    │   Super Admin User   │
                    │  (city@example.test) │
                    └──────────────────────┘
                             │
                    ┌────────┴────────┐
                    │                 │
            ┌───────▼────────┐    ┌───▼─────────┐
            │  Web Interface │    │  CLI Commands
            │  /super/tenants│    │  php artisan
            └───────────────┘    └──────────────┘
                    │                 │
         ┌──────────┴──────────┐      │
         │                     │      │
    ┌────▼─────────┐      ┌────▼─────┐
    │  Create      │      │  Delete   │
    │  Barangays   │      │  Barangays│
    │  Manage Users│      │  (CASCADE)│
    └──────────────┘      └──────────┘
         │                     │
         │                     │
    ┌────▼──────────────────┬─▼─────────────────┐
    │   DATABASE            │                   │
    ├───────────────────────┤                   │
    │ tenants               │ Deleted Records:  │
    │ ├─ id                 │ ├─ incidents      │
    │ ├─ name               │ ├─ mediations     │
    │ ├─ slug               │ ├─ patrol_logs    │
    │ ├─ subdomain          │ ├─ blotter_reqs   │
    │ ├─ custom_domain      │ └─ tenant_user    │
    │ ├─ plan_id            │                   │
    │ └─ is_active          │ Preserved:        │
    │                       │ ├─ users          │
    │ plans                 │ └─ plans          │
    │ ├─ Basic              │                   │
    │ ├─ Standard           │                   │
    │ └─ Premium            │                   │
    │                       │                   │
    │ users (many-to-many)  │                   │
    │ via tenant_user       │                   │
    └───────────────────────┴───────────────────┘
```

---

## 🚀 Workflow Diagram

### Creation Flow

```
START
  │
  ├─→ Web Interface?
  │   └─→ /super/tenants/create
  │       ├─ Fill form
  │       ├─ Validate
  │       └─ Create tenant
  │
  └─→ CLI?
      └─→ php artisan tenant:create
          ├─ Show tenants list
          ├─ Prompt for slug
          ├─ Prompt for details
          └─ Create tenant
  │
  └─→ Database
      └─→ INSERT INTO tenants
```

### Deletion Flow

```
START
  │
  ├─→ Web Interface?
  │   └─→ /super/tenants/{id}/edit
  │       ├─ Show form
  │       ├─ Click "Delete Barangay"
  │       ├─ Modal confirmation
  │       ├─ Type barangay name
  │       └─ POST /super/tenants/{id} (DELETE)
  │
  └─→ CLI?
      └─→ php artisan tenant:delete
          ├─ Show tenants list
          ├─ Prompt for slug
          ├─ Show tenant info
          ├─ Show records count
          ├─ Confirm delete
          ├─ Type barangay name
          └─ Execute delete
  │
  └─→ Validation
      ├─ Is tenant found?
      ├─ Is user Super Admin?
      └─ Does name match?
  │
  └─→ Database Transaction
      ├─ DELETE incidents
      ├─ DELETE mediations
      ├─ DELETE patrol_logs
      ├─ DELETE blotter_requests
      ├─ DELETE tenant_user (associations)
      └─ DELETE tenant
  │
  └─→ Result
      ├─ Success → Redirect with message
      └─ Error → Display error message
```

---

## 🔄 Component Interaction

```
┌─────────────────────────────────────┐
│     SuperAdminController.php         │
├─────────────────────────────────────┤
│                                     │
│  Method: deleteTenant()             │
│  ├─ Validate confirmation           │
│  ├─ Create transaction              │
│  └─ Delete records & tenant         │
│                                     │
│  Route: DELETE /super/tenants/{id}  │
│                                     │
└─────────────────────────────────────┘
           │
           ├─→ Validates request
           ├─→ Finds tenant
           ├─→ Checks permissions
           └─→ Executes transaction
                     │
        ┌────────────┴────────────┐
        │                         │
    ┌───▼─────────┐        ┌──────▼──────┐
    │ DeleteTenant│        │   Tenant    │
    │  Command    │        │   Model     │
    │             │        │             │
    │ CLI Tool    │        │ Database    │
    │ for Artisan │        │ Methods     │
    └─────────────┘        └─────────────┘
```

---

## 🔌 Request Flow - Web Deletion

```
User in Browser
    ↓
GET /super/tenants/1/edit
    ↓
SuperAdminController::editTenant()
    ↓ (Inertia)
Renders TenantForm.jsx with tenant data
    ↓
User clicks "Delete Barangay" button
    ↓
Modal shows:
  ├─ Tenant name & information
  ├─ Count of affected records
  ├─ Warning message
  └─ Input field for confirmation
    ↓
User types barangay name
    ↓
User clicks "Confirm Deletion"
    ↓
POST /super/tenants/1 (method DELETE)
    ↓
SuperAdminController::deleteTenant()
    ├─ Validates confirmation
    └─ Executes transaction
    ↓
Redirect to /super/tenants
    ↓
Display success message
```

---

## 🔌 Request Flow - CLI Deletion

```
Terminal
    ↓
php artisan tenant:delete
    ↓
DeleteTenantCommand::handle()
    ├─ Check for slug argument
    └─ If missing, show tenants list
    ↓
User enters slug
    ↓
Find tenant
    ↓
Display tenant information:
  ├─ ID, name, slug, barangay
  ├─ Count of incidents
  ├─ Count of mediations
  ├─ Count of patrol logs
  ├─ Count of blotter requests
  └─ Count of user associations
    ↓
Ask: "Are you sure?"
    ↓
If yes, ask: "Type barangay name to confirm"
    ↓
Validate name matches
    ↓
If match, execute transaction:
  ├─ DELETE incidents
  ├─ DELETE mediations
  ├─ DELETE patrol_logs
  ├─ DELETE blotter_requests
  ├─ DETACH users
  └─ DELETE tenant
    ↓
Display success message
```

---

## 📦 Data Deletion Cascade

```
Tenant
  ├─ Incidents (HasMany)
  │  ├─ incident_attachments (via Incident)
  │  └─ mediation_participants (via Incident)
  │
  ├─ Mediations (HasMany)
  │  └─ records cascade delete
  │
  ├─ PatrolLogs (HasMany)
  │  └─ records cascade delete
  │
  ├─ BlotterRequests (HasMany)
  │  └─ records cascade delete
  │
  └─ Users (BelongsToMany)
     └─ tenant_user pivot only detached
        (users not deleted)
```

---

## 🎯 Routes Map

```
Super Admin Routes
├─ GET    /super/tenants
│         → View all barangays (SuperAdminController@tenants)
│
├─ GET    /super/tenants/create
│         → Show create form (SuperAdminController@createTenant)
│
├─ POST   /super/tenants
│         → Store new barangay (SuperAdminController@storeTenant)
│
├─ GET    /super/tenants/{tenant}/edit
│         → Show edit form with delete button (SuperAdminController@editTenant)
│
├─ PUT    /super/tenants/{tenant}
│         → Update barangay (SuperAdminController@updateTenant)
│
├─ DELETE /super/tenants/{tenant}        ← NEW
│         → Delete barangay (SuperAdminController@deleteTenant)
│
├─ POST   /super/tenants/{tenant}/toggle
│         → Activate/deactivate (SuperAdminController@toggleActive)
│
└─ [User management routes...]
```

---

## 💾 Database Transactions

```
BEGIN TRANSACTION
    ↓
DELETE FROM incidents WHERE tenant_id = ?
    ↓
DELETE FROM mediations WHERE tenant_id = ?
    ↓
DELETE FROM patrol_logs WHERE tenant_id = ?
    ↓
DELETE FROM blotter_requests WHERE tenant_id = ?
    ↓
DELETE FROM tenant_user WHERE tenant_id = ?
    ↓
DELETE FROM tenants WHERE id = ?
    ↓
COMMIT ← All or nothing!
    ↓
If error anywhere → ROLLBACK
```

---

## 🛡️ Validation Layers

```
Request Deletion
    ↓
┌─────────────────────────────────┐
│ Layer 1: Route Protection       │
├─────────────────────────────────┤
│ ✓ Must be authenticated         │
│ ✓ Must have super_admin role    │
└─────────────────────────────────┘
    ↓
┌─────────────────────────────────┐
│ Layer 2: Model Validation       │
├─────────────────────────────────┤
│ ✓ Tenant must exist             │
│ ✓ Can find by ID or slug        │
└─────────────────────────────────┘
    ↓
┌─────────────────────────────────┐
│ Layer 3: User Confirmation      │
├─────────────────────────────────┤
│ ✓ Name must match exactly       │
│ (case-sensitive)                │
└─────────────────────────────────┘
    ↓
┌─────────────────────────────────┐
│ Layer 4: Transaction Safety     │
├─────────────────────────────────┤
│ ✓ All-or-nothing operation      │
│ ✓ Atomic delete                 │
│ ✓ Rollback on error             │
└─────────────────────────────────┘
    ↓
✅ Safe Deletion
```

---

## 📊 State Diagram

```
Tenant States During Deletion

Initial:     ┌─────────────────┐
             │   Active Tenant │
             │   (is_active:1) │
             └────────┬────────┘
                      │
                  DELETE initiated
                      │
            ┌─────────▼──────────┐
            │ Validation Phase   │
            ├────────────────────┤
            │ ✓ Auth check       │
            │ ✓ Tenant exists    │
            │ ✓ Name matches     │
            └─────────┬──────────┘
                      │ ✅ Valid
                      │
            ┌─────────▼──────────────┐
            │ Transaction Execution  │
            ├────────────────────────┤
            │ All deletes begin      │
            │ Incidents: DELETE      │
            │ Mediations: DELETE     │
            │ Patrol logs: DELETE    │
            │ Blotter: DELETE        │
            │ Users: DETACH          │
            │ Tenant: DELETE         │
            └─────────┬──────────────┘
                      │
         ┌────────────┴────────────┐
         │ Success?               │
         └────────────┬───────────┘
                      │
         ┌────────────┴──────────────┐
         │                           │
      Yes│                           │No
        ↓                            ↓
   COMMIT                         ROLLBACK
        │                            │
   ┌────▼──────────┐         ┌──────▼────────┐
   │   Record      │         │  Original     │
   │   Deleted ✅  │         │  State ✅     │
   │   Forever     │         │  (unchanged)  │
   └───────────────┘         └───────────────┘
```

---

## 🔧 Class & Method Map

```
SuperAdminController
├─ public deleteTenant(Request, Tenant): RedirectResponse
│  ├─ Validates request
│  ├─ Validates name confirmation
│  ├─ Executes DB transaction
│  ├─ Deletes related records
│  └─ Returns redirect

DeleteTenantCommand extends Command
├─ public handle(): int
│  ├─ Get tenant slug (interactive)
│  ├─ Find tenant
│  ├─ Display information
│  ├─ Count related records
│  ├─ Ask for confirmation
│  ├─ Validate name match
│  ├─ Execute transaction
│  └─ Show result
│
├─ protected displayTenantsList(): void
│  ├─ Fetch tenants
│  ├─ Format as table
│  └─ Display to user
```

---

**Visual documentation complete!** 🎨

See `TENANT_MANAGEMENT.md` for detailed usage guide.
