# System Startup Process - Step by Step

## ✅ Pre-Flight Checklist

Before starting, verify you have:
- [ ] PHP 8.2+ installed
- [ ] Composer installed
- [ ] Node.js & npm installed
- [ ] Internet connection (for initial setup)
- [ ] Project folder accessible at `c:\Users\Francis\Desktop\blotter\Barangayblotter`

---

## 🎯 QUICK START (5 minutes)

### If this is your FIRST TIME:

```powershell
# Navigate to project
cd c:\Users\Francis\Desktop\blotter\Barangayblotter

# 1. Install dependencies
composer install
npm install

# 2. Setup environment
copy .env.example .env
php artisan key:generate

# 3. Create & seed database
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan migrate:fresh --seed

# 4. Link storage & build assets
php artisan storage:link
npm run build

# 5. Start the servers (two separate terminals)
# TERMINAL 1:
php artisan serve

# TERMINAL 2 (optional, for hot reload):
npm run dev
```

**Then open**: http://127.0.0.1:8000

---

### If you ALREADY HAVE everything set up:

```powershell
cd c:\Users\Francis\Desktop\blotter\Barangayblotter

# Just start the servers

# TERMINAL 1:
php artisan serve

# TERMINAL 2 (optional):
npm run dev
```

**Then open**: http://127.0.0.1:8000

---

## 📊 Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│          YOUR BROWSER (Chrome/Firefox)              │
│        http://127.0.0.1:8000 or :8001              │
└────────────┬────────────────────────────────────────┘
             │
             ├─────────────────────────────────────────┐
             │                                         │
    ┌────────▼─────────────┐          ┌───────────────▼────────┐
    │  Laravel Backend     │          │   Vite Dev Server      │
    │  (php artisan serve) │          │  (npm run dev)         │
    │  Port: 8000          │          │  Port: 5173            │
    │                      │          │  (Optional, hot reload)│
    │  - Routes            │          │                        │
    │  - Controllers       │          │  - React Components    │
    │  - Models/Database   │          │  - CSS/JS transpiling  │
    │  - Authentication    │          │  - Live updates        │
    └────────┬─────────────┘          └───────────┬────────────┘
             │                                    │
             └────────────────────┬───────────────┘
                                  │
                      ┌───────────▼──────────┐
                      │   SQLite Database    │
                      │  database.sqlite     │
                      │                      │
                      │  - Users             │
                      │  - Tenants           │
                      │  - Incidents         │
                      │  - Mediations        │
                      │  - Patrol Logs       │
                      └──────────────────────┘
```

---

## 🔄 REQUEST FLOW

```
1. User opens http://127.0.0.1:8000
                        │
2. Browser contacts Laravel Server (Port 8000)
                        │
3. Laravel routes request to appropriate Controller
                        │
4. Controller queries Database (SQLite)
                        │
5. Laravel returns React Component (Inertia)
                        │
6. React renders UI + JavaScript
                        │
7. Vite Dev Server (Port 5173) serves assets + hot reload
                        │
8. User sees Dashboard/Page
```

---

## 🛠️ DETAILED SETUP WALKTHROUGH

### Step 1️⃣: Install PHP Dependencies
```powershell
cd c:\Users\Francis\Desktop\blotter\Barangayblotter
composer install
```
**What happens:**
- Downloads all PHP packages from `composer.json`
- Creates `vendor/` folder with dependencies
- Generates `composer.lock` for consistency

**Time**: ~30-60 seconds (depends on internet speed)

---

### Step 2️⃣: Install JavaScript Dependencies
```powershell
npm install
```
**What happens:**
- Downloads all npm packages from `package.json`
- Creates `node_modules/` folder
- Prepares Vite, React, Inertia

**Time**: ~1-2 minutes

---

### Step 3️⃣: Create Environment File
```powershell
copy .env.example .env
```
**What this is:**
- Copies the example config to your local config
- Contains database, mail, API keys, etc.

**Never commit `.env` to Git** (it has secrets)

---

### Step 4️⃣: Generate Application Key
```powershell
php artisan key:generate
```
**What this does:**
- Generates a 32-character encryption key
- Stores in `.env` as `APP_KEY`
- Used for session encryption, CSRF tokens, etc.

**Output**: `Application key set successfully.`

---

### Step 5️⃣: Create Database File
```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
```
**What this creates:**
- An empty SQLite database file
- Located at `database/database.sqlite`
- Holds all your data (users, incidents, etc.)

---

### Step 6️⃣: Run Migrations & Seed
```powershell
php artisan migrate:fresh --seed
```
**What this does:**
1. Drops all existing tables (--fresh)
2. Runs migrations to create tables
3. Runs seeders to populate demo data

**Tables created:**
- `users` - System users
- `tenants` - Barangays
- `incidents` - Logged incidents
- `mediations` - Mediation sessions
- `patrol_logs` - Patrol records
- `blotter_requests` - Copy requests
- And more...

**Demo data:**
- 3 plans (Basic, Standard, Premium)
- 5 barangays (Casisang, Sumpong, San Jose, Kalasungay, Caburacanan)
- 3 demo users (see credentials below)

**Time**: ~2-5 seconds

---

### Step 7️⃣: Link Storage
```powershell
php artisan storage:link
```
**What this does:**
- Creates symlink to `storage/app/public`
- Allows users to upload incident attachments
- Files stored at `/storage/app/public/incidents/`

---

### Step 8️⃣: Build Frontend Assets
```powershell
npm run build
```
**What this does:**
- Compiles React JSX to JavaScript
- Compiles Tailwind CSS
- Bundles for production
- Creates `public/build/` folder

**Use `npm run build` when:**
- Deploying to production
- Making one-time changes
- Not actively developing

---

### Step 9️⃣: Start Laravel Server
**OPEN TERMINAL 1**
```powershell
php artisan serve
```
**What you'll see:**
```
   Laravel development server started: http://127.0.0.1:8000
```

**This:**
- Starts a local web server on port 8000
- Keep this terminal open while working
- Handles all HTTP requests
- Connects to database

---

### Step 🔟: Start Vite Dev Server (Optional but Recommended)
**OPEN TERMINAL 2**
```powershell
npm run dev
```
**What you'll see:**
```
  VITE v... dev server running at:
  ➜  Local:   http://localhost:5173/
```

**This:**
- Starts hot-reload development server
- Recompiles assets on file changes
- Injects changes without page refresh
- Much faster development

**Use this when:**
- Actively developing frontend
- Making React component changes
- Modifying CSS/JavaScript

**Optional if:**
- Just testing without making changes
- Just using production build

---

## ✨ YOUR FIRST LOGIN

**Navigate to**: http://127.0.0.1:8000

**Login with**:
- Email: `admin@malaybalay.test`
- Password: `password`

**What you'll see:**
1. Login page
2. Select barangay (Casisang, etc.)
3. Dashboard with statistics
4. Incident list, mediations, patrol logs

---

## 🎨 Making Changes

**If you're developing:**

### Changing React Components
1. Edit file in `resources/js/`
2. Watch hot reload happen automatically
3. Browser refreshes instantly
4. No page reload needed

### Changing CSS
1. Edit `resources/css/` or Tailwind classes in components
2. Auto-compiled and injected
3. See changes immediately

### Changing Backend (PHP)
1. Edit in `app/` folder
2. May need to refresh browser
3. If major changes, restart `php artisan serve`

### Changing Database Schema
1. Create new migration: `php artisan make:migration name`
2. Run: `php artisan migrate`
3. May need to restart servers

---

## 🔑 All Demo Credentials

| Role | Email | Password | Scope |
|------|-------|----------|-------|
| **Barangay Staff** | `admin@malaybalay.test` | `password` | All 5 barangays |
| **City Admin** | `city@malaybalay.test` | `password` | City dashboard |
| **Super Admin** | `admin@admin` | `admin` | Everything |

---

## 🐛 Troubleshooting Quick Reference

| Problem | Solution |
|---------|----------|
| "No such table" error | `php artisan migrate` |
| Port 8000 in use | `php artisan serve --port=8001` |
| npm not found | Install Node.js from nodejs.org |
| PHP not found | Install PHP 8.2+ or add to PATH |
| Database locked | Kill other Laravel processes |
| "Key not set" | `php artisan key:generate` |
| Assets not loading | `npm run build` or restart `npm run dev` |
| Old CSS/JS cached | Hard refresh (Ctrl+Shift+R) |

---

## 📱 Accessing from Other Devices

To access from another computer on your network:

1. Find your local IP: `ipconfig` (look for IPv4)
2. Example: `192.168.1.100`
3. Change `.env`: `APP_URL=http://192.168.1.100:8000`
4. Restart Laravel: `php artisan serve --host=0.0.0.0`
5. Other device: `http://192.168.1.100:8000`

---

## ⚡ Performance Tips

### Backend
- Use `php artisan cache:clear` between migrations
- Use Tinker for quick testing: `php artisan tinker`
- Enable query logging in `.env` for debugging

### Frontend
- Keep `npm run dev` running for hot reload
- Don't close browser dev tools excessively
- Use production build when deploying

### Database
- SQLite is fine for development
- Consider MySQL for production (faster, better scaling)
- Regular backups of `database.sqlite`

---

## 📋 Commands Cheatsheet

```powershell
# Database
php artisan migrate:fresh --seed    # Reset everything
php artisan migrate                 # Run migrations
php artisan db:seed                 # Seed demo data
php artisan tinker                  # Interactive shell

# Servers
php artisan serve                   # Start Laravel (Port 8000)
npm run dev                         # Start Vite (Port 5173)
npm run build                       # Build for production

# Development
php artisan make:model Name         # Create model
php artisan make:controller Name    # Create controller
php artisan make:migration name     # Create migration
php artisan routes:list             # Show all routes

# Maintenance
php artisan cache:clear             # Clear caches
php artisan config:clear            # Clear config
composer update                     # Update packages
npm update                          # Update npm packages
```

---

## 🎉 Success Checklist

After startup, you should see:

- ✅ Laravel server running on http://127.0.0.1:8000
- ✅ Vite dev server running (optional)
- ✅ Can login with `admin@malaybalay.test`
- ✅ Can see dashboard with statistics
- ✅ Can create incidents
- ✅ Can switch barangays

---

## 📞 Need Help?

1. **Check logs**: `storage/logs/laravel.log`
2. **Restart servers**: Close terminals, run commands again
3. **Clear cache**: `php artisan cache:clear`
4. **Fresh database**: `php artisan migrate:fresh --seed`
5. **Read docs**: See `README.md` and `TENANCY_INTEGRATION.md`

---

**Ready to start?**

```powershell
cd c:\Users\Francis\Desktop\blotter\Barangayblotter
php artisan serve
npm run dev    # in another terminal
# Open http://127.0.0.1:8000
```

Happy coding! 🚀
