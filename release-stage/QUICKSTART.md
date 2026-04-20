# Quick Start Guide - Barangay Blotter System

## 🚀 Starting the System in 5 Minutes

### Prerequisites

- PHP 8.2+ (check: `php --version`)
- Composer (check: `composer --version`)
- Node.js & npm (check: `node --version`)
- Git (check: `git --version`)

---

## Option 1: First Time Setup (Fresh Install)

### Step 1: Install Dependencies

```powershell
# Install PHP packages
composer install

# Install JavaScript packages
npm install
```

### Step 2: Setup Environment

```powershell
# Copy example environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### Step 3: Setup Database

```powershell
# Create empty SQLite database file
New-Item -ItemType File -Path database\database.sqlite -Force

# Run migrations and seed demo data
php artisan migrate:fresh --seed
```

### Step 4: Setup Storage & Assets

```powershell
# Create storage link for file uploads
php artisan storage:link

# Build frontend assets
npm run build
```

### Step 5: Start the Application

**Terminal 1 - Laravel Server:**

```powershell
php artisan serve
```

**Terminal 2 - Frontend Dev Server (optional, for hot reload):**

```powershell
npm run dev
```

**Open in browser:** http://127.0.0.1:8000

---

## Option 2: Quick Start (Already Have Database)

If you already have the database and just need to get the server running:

```powershell
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start frontend dev (optional)
npm run dev
```

Open http://127.0.0.1:8000 in your browser.

---

## 🔐 Demo Login Credentials

### Super Admin (City-wide Access)

- **Email**: `city@malaybalay.test`
- **Password**: `password`
- **Access**: City admin dashboard, all barangays monitoring

### Barangay Staff

- **Email**: `admin@malaybalay.test`
- **Password**: `password`
- **Access**: Dashboard, incidents, mediations, patrol logs, blotter requests
- **Barangays**: Casisang, Sumpong, San Jose, Kalasungay, Caburacanan

### Alternative Super Admin

- **Email**: `admin@admin`
- **Password**: `admin`
- **Access**: Full admin capabilities

---

## 📋 Available Commands

### Database

```powershell
# Initial setup (wipes DB, rebuilds, adds demo data)
php artisan migrate:fresh --seed

# Just run migrations
php artisan migrate

# Just run seeders
php artisan db:seed

# Reset everything
php artisan migrate:reset
```

### Frontend

```powershell
# Build once
npm run build

# Watch for changes (hot reload)
npm run dev
```

### Testing

```powershell
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/TenancyTest.php

# With code coverage
php artisan test --coverage
```

### Artisan

```powershell
# List all available commands
php artisan list

# Launch Tinker (interactive console)
php artisan tinker

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🔧 Common Issues & Solutions

### Issue: "No application key has been generated"

```powershell
php artisan key:generate
```

### Issue: "SQLSTATE[HY000]: General error: 1 no such table"

Your database needs migrations. Run:

```powershell
php artisan migrate
```

### Issue: "Could not open input file: artisan"

Make sure you're in the project root directory:

```powershell
cd c:\Users\Francis\Desktop\blotter\Barangayblotter
```

### Issue: "Port 8000 already in use"

Specify a different port:

```powershell
php artisan serve --port=8001
```

### Issue: "npm: The term 'npm' is not recognized"

Node.js is not installed or not in PATH. Download and install from https://nodejs.org/

### Issue: Frontend not updating

Make sure `npm run dev` is running in a separate terminal.

---

## 📁 Project Structure

```
Barangayblotter/
├── app/                    # Laravel app code
│   ├── Models/            # Database models
│   ├── Http/Requests/     # Request validation
│   ├── Http/Controllers/  # Request handlers
│   └── Providers/         # Service providers
├── config/                 # Configuration files
├── database/               # Migrations & seeders
│   ├── migrations/         # Database schema
│   └── seeders/            # Demo data
├── resources/              # Frontend files
│   ├── css/               # Tailwind CSS
│   ├── js/                # React components
│   └── views/             # Inertia templates
├── routes/                 # Route definitions
├── storage/               # File storage (attachments)
├── tests/                 # Test files
├── vendor/                # PHP dependencies
├── node_modules/          # JavaScript dependencies
├── .env                   # Environment config (create from .env.example)
├── database.sqlite        # SQLite database file
└── artisan               # Laravel CLI
```

---

## 🎯 Key Features to Try

### 1. **Login & Barangay Selection**

- Go to `/login`
- Use `admin@malaybalay.test` / `password`
- Select a barangay (e.g., Casisang)

### 2. **Create an Incident**

- Dashboard → Incidents → Create New
- Fill in form and attach a file
- View in incident list

### 3. **Schedule Mediation** (Standard/Premium plans)

- Dashboard → Mediations → Create New
- Select incident
- Assign mediator and schedule date

### 4. **View City Dashboard** (Super Admin)

- Logout and login as `city@malaybalay.test`
- View city-wide statistics
- Monitor all barangays

### 5. **Switch Barangays**

- Use tenant switcher in top navbar
- Switch to different barangay
- Data automatically scoped

---

## 🐛 Testing the System

### Run Feature Tests

```powershell
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/TenancyTest.php

# Single test method
php artisan test --filter test_incident_queries_are_scoped_by_tenant
```

### Interactive Testing (Tinker)

```powershell
php artisan tinker

# Try these commands:
>>> $tenant = \App\Models\Tenant::first()
>>> $tenant->name
>>> \App\Models\Incident::count()
>>> session(['current_tenant_id' => $tenant->id])
>>> \App\Models\Incident::all()->count()
```

---

## 🚀 Deployment Notes

When deploying to production:

1. Copy `.env.example` to `.env` and set production values
2. Set `APP_DEBUG=false`
3. Run `php artisan key:generate` if new install
4. Run `php artisan migrate --force`
5. Run `npm run build` to compile assets
6. Use a proper web server (Apache/Nginx) instead of `php artisan serve`

---

## 📞 Support

- **Laravel Docs**: https://laravel.com/docs
- **Tenancy for Laravel**: https://tenancyforlaravel.com/docs
- **React/Inertia**: https://inertiajs.com/

See `TENANCY_INTEGRATION.md` for multi-tenancy details.

---

## 📝 Summary

```powershell
# NEW INSTALLATION
composer install
npm install
copy .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan migrate:fresh --seed
php artisan storage:link
npm run build

# Then in two terminals:
# Terminal 1:
php artisan serve

# Terminal 2:
npm run dev

# Open: http://127.0.0.1:8000
# Login: admin@malaybalay.test / password
```
