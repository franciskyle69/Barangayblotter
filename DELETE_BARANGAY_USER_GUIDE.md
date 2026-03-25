# 🗑️ Delete Barangay in Central App - User Guide

## Overview

Super Admins can now delete barangays directly from the central app's barangay settings form. This provides a convenient web interface for managing and removing barangays.

---

## 🎯 How to Delete a Barangay

### Step 1: Navigate to Barangays
1. Go to **http://127.0.0.1:8000/super/tenants** (or your domain)
2. You'll see a list of all barangays

### Step 2: Click Edit
1. Find the barangay you want to delete
2. Click the **"Edit"** button

### Step 3: Scroll to Danger Zone
1. Scroll down to the bottom of the form
2. You'll see a red **"Danger Zone"** section
3. Click the **"Delete Barangay"** button

### Step 4: Confirm Deletion
A modal dialog will appear:

1. **Read the Warning**
   - Shows barangay name
   - Lists all data that will be deleted
   - Emphasizes this action is permanent

2. **Type Confirmation**
   - You must type the exact barangay name
   - This prevents accidental deletion
   - Text field is case-sensitive

3. **Confirm or Cancel**
   - Click **"Cancel"** to go back
   - Click **"Delete Permanently"** to proceed (button only enables when name matches)

### Step 5: Redirect
- After deletion, you'll be redirected to the barangays list
- The barangay is now permanently removed
- All associated data has been deleted

---

## ⚠️ What Gets Deleted

When you delete a barangay, these records are **permanently removed**:

✗ **Incidents**
- All incident records
- All incident attachments (photos, documents)

✗ **Mediations**
- All mediation records
- All mediation notes

✗ **Patrol Logs**
- All patrol log entries
- All patrol information

✗ **Blotter Requests**
- All blotter request records
- All request details

✗ **User Associations**
- User assignments to this barangay
- User-barangay relationships

### What is NOT Deleted

✓ **User Accounts**
- User accounts are preserved
- Users can be reassigned to other barangays

✓ **Plans**
- Plans remain in the system
- Can be used by other barangays

---

## 🛡️ Safety Features

### Multiple Protection Layers

1. **Visual Warning**
   - Red danger zone clearly marks delete section
   - Warning text explains consequences
   - Barangay name is displayed

2. **Modal Confirmation**
   - Second confirmation screen appears
   - Lists all affected data types
   - Shows exact barangay name

3. **Name Matching**
   - Must type exact barangay name
   - Case-sensitive (e.g., "Casisang" not "casisang")
   - Button disabled until name matches exactly

4. **Action Feedback**
   - Shows "Deleting…" while processing
   - Success or error message displayed
   - Auto-redirect on success

---

## 📸 UI Elements

### Danger Zone Section

Located at the bottom of the barangay edit form:

```
┌─────────────────────────────────────────┐
│ ⚠️ Danger Zone                          │
├─────────────────────────────────────────┤
│ Deleting this barangay will permanently│
│ remove all associated data including:  │
│ - Incidents                            │
│ - Mediations                           │
│ - Patrol logs                          │
│ - Blotter requests                     │
│                                        │
│ [Delete Barangay] (Red Button)        │
└─────────────────────────────────────────┘
```

### Confirmation Modal

```
┌──────────────────────────────────────────┐
│ Delete Barangay?                         │
├──────────────────────────────────────────┤
│ ⚠️ This action is permanent!            │
│                                          │
│ You are about to delete "Casisang"     │
│ and all its associated data:           │
│ • All incidents and attachments        │
│ • All mediations                       │
│ • All patrol logs                      │
│ • All blotter requests                 │
│ • User associations                    │
│                                          │
│ To confirm, type: "Casisang"           │
│ [________________]                      │
│                                          │
│ [Cancel]  [Delete Permanently]         │
└──────────────────────────────────────────┘
```

---

## ✅ Verification Checklist

Before deleting a barangay, verify:

- [ ] You have the correct barangay name
- [ ] This is the barangay you want to delete
- [ ] You've backed up any important data
- [ ] Users have been notified
- [ ] All incidents are processed/archived
- [ ] It's safe to delete this barangay

---

## ❓ FAQ

<details>
<summary><b>Can I undo a deletion?</b></summary>

No, deletion is permanent. There is no undo. Make sure you have database backups if needed.

</details>

<details>
<summary><b>What if I make a mistake?</b></summary>

If you delete the wrong barangay:
1. You cannot undo it through the app
2. You need to restore from a database backup
3. Keep regular backups to prevent data loss

</details>

<details>
<summary><b>Can regular barangay users delete their barangay?</b></summary>

No, only Super Admins can delete barangays. Regular users cannot access the deletion feature.

</details>

<details>
<summary><b>What happens to assigned users?</b></summary>

Users assigned to the deleted barangay are detached from it, but their user accounts remain. They can be reassigned to other barangays.

</details>

<details>
<summary><b>What if deletion fails?</b></summary>

If deletion encounters an error:
1. An error message will appear
2. The barangay is NOT deleted (transaction rolled back)
3. Check the error message for details
4. Try again or contact support

</details>

<details>
<summary><b>Why require typing the barangay name?</b></summary>

Typing the barangay name prevents accidental deletions. It ensures you're aware of exactly which barangay is being deleted.

</details>

---

## 🔧 Technical Details

### Component
**File**: `resources/js/Pages/Super/TenantForm.jsx`

### Key Features
- Uses React hooks (useState)
- Inertia router for DELETE request
- Modal-based confirmation
- Real-time name validation
- Disabled button state management

### Validation
- Exact name match required
- Case-sensitive comparison
- Delete button disabled until confirmed
- Error handling with alerts

### Backend Route
```
DELETE /super/tenants/{tenant}
```

Handled by: `SuperAdminController@deleteTenant`

---

## 🚀 Alternative Methods

You can also delete barangays using:

### CLI Command
```bash
php artisan tenant:delete barangay-slug
```

See: `TENANT_MANAGEMENT.md` for CLI details

### API
```bash
curl -X DELETE http://127.0.0.1:8000/super/tenants/1 \
  -H "Content-Type: application/json" \
  -d '{"confirmation": "Casisang"}'
```

---

## 📞 Support

**Issue**: Delete button doesn't appear
- **Solution**: Make sure you're editing an existing barangay, not creating a new one

**Issue**: Modal doesn't appear
- **Solution**: Make sure JavaScript is enabled in your browser

**Issue**: Button disabled even when name matches
- **Solution**: Check spelling and case - name must match exactly

**Issue**: Deletion fails
- **Solution**: Check browser console (F12) for error messages, or contact your system administrator

---

## 🔒 Access Control

- **Who can delete**: Only Super Admin users
- **Via Web UI**: Edit button on barangays list
- **Via CLI**: Artisan command (admin only)
- **Via API**: DELETE endpoint (authenticated Super Admin)

---

**Last Updated**: March 25, 2026  
**Version**: 1.0  
**Status**: Production Ready ✅
