# 🔧 Delete Button Not Showing - Troubleshooting

## Problem

The red "Delete Barangay" button is not appearing on the barangay edit form.

---

## ✅ Solutions (Try in Order)

### Solution 1: Rebuild Frontend Assets

The React component needs to be recompiled:

```bash
npm run build
```

Wait for it to finish, then refresh your browser.

**If using development mode**:
```bash
npm run dev
```

Keep this running in another terminal while testing.

---

### Solution 2: Hard Refresh Browser

Clear the cache:

**Windows/Linux**:
```
Ctrl + Shift + Delete
```

**Mac**:
```
Cmd + Shift + Delete
```

Or press **Ctrl + Shift + R** (or **Cmd + Shift + R** on Mac)

---

### Solution 3: Clear Browser Cache

1. Open DevTools (**F12**)
2. Right-click on the refresh button
3. Select **"Empty cache and hard refresh"**

---

### Solution 4: Check You're in Edit Mode

The delete button **only appears when editing an existing barangay**.

✅ **Correct**: `/super/tenants/1/edit` (shows delete)  
❌ **Wrong**: `/super/tenants/create` (no delete)

Make sure you:
1. Go to `/super/tenants` list
2. Click **"Edit"** on a barangay
3. Scroll to **bottom** of form

---

### Solution 5: Clear Laravel Cache

Run these commands:

```bash
php artisan cache:clear
php artisan config:cache
php artisan view:clear
```

Then restart Laravel:
```bash
php artisan serve
```

---

### Solution 6: Check Browser Console

Open **DevTools** (F12):

1. Click **"Console"** tab
2. Look for **red errors**
3. Note the error message
4. Share it with support

---

### Solution 7: Full Reset

```bash
# 1. Clear everything
npm run build
php artisan cache:clear
php artisan config:cache
php artisan view:clear

# 2. Restart Laravel
php artisan serve

# 3. Restart Vite (in another terminal if using dev)
npm run dev

# 4. Hard refresh browser
Ctrl + Shift + R
```

---

## 🧪 Step-by-Step Test

Follow these exact steps:

1. **Open Terminal 1**: Start Laravel server
   ```bash
   php artisan serve
   ```
   Keep this running.

2. **Open Terminal 2**: Start Vite (optional but recommended)
   ```bash
   npm run dev
   ```
   Keep this running.

3. **Open Browser**: Go to Super Admin
   ```
   http://127.0.0.1:8000/super/tenants
   ```

4. **Log In** if needed
   - Email: `city@malaybalay.test`
   - Password: `password`

5. **Click "Edit"** on any barangay

6. **Scroll Down** to the bottom of the form

7. **Look for Red "Danger Zone"** section

8. **If you see it**: ✅ Delete button should be there!
   - Type barangay name
   - Click delete button
   - Confirm in modal

9. **If you don't see it**: 😞 Try solutions above

---

## 🔍 Verify Code is There

The code should be in: `resources/js/Pages/Super/TenantForm.jsx`

Look for these sections:

### Danger Zone Section (Line ~152)
```jsx
{/* Delete Danger Zone */}
{isEditing && (
  <div className="rounded-lg border-2 border-red-200 bg-red-50 p-6">
    <div className="mb-4">
      <h3 className="text-lg font-semibold text-red-900">⚠️ Danger Zone</h3>
      ...
```

### Delete Modal Section (Line ~176)
```jsx
{/* Delete Confirmation Modal */}
{showDeleteModal && (
  <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    ...
```

If both sections are there, the code is correct.

---

## 📝 What to Check

✅ Are you **editing** (not creating) a barangay?
✅ Have you **rebuilt** with `npm run build`?
✅ Did you **hard refresh** the browser?
✅ Are you **logged in as Super Admin**?
✅ Is Laravel server **running**?
✅ Is the code **in the file**?

---

## 🐛 Common Issues

### Issue: Page shows old version
**Solution**: Run `npm run build` and hard refresh (Ctrl+Shift+R)

### Issue: Can't find barangays to edit
**Solution**: Go to `/super/tenants` list and click Edit button

### Issue: Delete button appears, but won't work
**Solution**: Check browser console (F12) for errors

### Issue: Modal won't show
**Solution**: Try hard refresh and clear cache

### Issue: Delete button shows but is grayed out
**Solution**: Type the exact barangay name to enable it

---

## 📞 Still Not Working?

Please provide:

1. **Screenshot** of the page (F12 open)
2. **URL** you're visiting
3. **Browser console errors** (F12 → Console tab)
4. **Output of**:
   ```bash
   npm run build
   ```

---

## ✨ Quick Checklist

- [ ] Ran `npm run build`
- [ ] Hard refreshed browser (Ctrl+Shift+R)
- [ ] Logged in as Super Admin
- [ ] Visiting `/super/tenants/{id}/edit` (not create)
- [ ] Scrolled to bottom of form
- [ ] Can see red "Danger Zone" section
- [ ] Delete button is visible
- [ ] Type barangay name and it enables
- [ ] Click delete and modal appears

If all checked ✅, the feature works!

---

**Last Updated**: March 25, 2026  
**Status**: Troubleshooting Guide
