# Malaybalay City Barangay Blotter System

A multi-tenant Laravel web system for **Malaybalay City, Bukidnon** barangay offices to record blotters, incidents, mediations, and patrol logs with strict data isolation per barangay.

## Features

- **Multi-tenancy**: Each barangay of Malaybalay City is a tenant (e.g. Casisang, Sumpong, San Jose). Users belong to one or more barangays with a role per barangay.
- **Plans**: Basic (200 incidents/month), Standard (2000/month, online complaints, mediation, SMS, analytics), Premium (unlimited, auto case numbers, QR verification, central monitoring).
- **Roles**: Barangay Secretary, Barangay Captain, Community Watch, Community Mediator, Resident.
- **Core functions**: Incident/blotter logging, case status (Open, Under Mediation, Settled, Escalated), mediation scheduling, patrol logs, blotter/certified copy requests.
- **City-level monitoring**: Central dashboard for Malaybalay City to monitor all barangays (super admin).

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm (for Vite, React, Inertia)
- SQLite (default) or MySQL / MariaDB

---

## Run the application on another PC

Follow these steps on any machine where you want to run the project (after cloning from GitHub or copying the folder).

### 1. Get the code

```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git
cd YOUR_REPO
```

Or copy the project folder to the new PC (still do the steps below).

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Environment file

Do **not** copy `.env` from another PC (it may contain machine-specific paths). Create a new one from the example:

```bash
copy .env.example .env
```

On macOS/Linux:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Edit `.env` and set at least:

- `APP_NAME="Malaybalay City Barangay Blotter"`
- `APP_URL=http://127.0.0.1:8000` (or your local URL)

**SQLite (default in `.env.example`):**

```env
DB_CONNECTION=sqlite
# DB_DATABASE is optional; Laravel uses database/database.sqlite by default
```

Create the empty database file once:

```bash
# Windows PowerShell
New-Item -ItemType File -Path database\database.sqlite -Force

# macOS / Linux
touch database/database.sqlite
```

**MySQL instead:** uncomment and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and set `DB_CONNECTION=mysql`.

### 4. Database: migrate and seed

Create tables and load demo data (plans, barangays, users):

```bash
php artisan migrate
php artisan db:seed
```

**If you want to wipe the database and start clean** (all data removed, then re-seeded):

```bash
php artisan migrate:fresh --seed
```

### 5. Storage (incident attachments)

```bash
php artisan storage:link
```

### 6. Frontend assets

The UI uses Vite + React + Inertia. Install JavaScript dependencies and build:

```bash
npm install
npm run build
```

For development with hot reload, use a **second terminal**:

```bash
npm run dev
```

Keep `npm run dev` running while you work; otherwise rely on `npm run build` after code changes.

### 7. Run the server

```bash
php artisan serve
```

Open **http://127.0.0.1:8000** in your browser.

---

## Seeding users and demo data

Seeding fills the database with **plans**, **barangays (tenants)**, and **users** so you can log in immediately.

### Commands

| Command | What it does |
|--------|----------------|
| `php artisan db:seed` | Runs all seeders (see below). Safe if tables already exist and you only need to add missing seed data (may duplicate if not careful—prefer `migrate:fresh --seed` for a clean slate). |
| `php artisan migrate:fresh --seed` | **Drops all tables**, runs migrations again, then seeds. Use for a clean install or when you want to reset everything. |
| `php artisan db:seed --class=PlanSeeder` | Only plans (Basic, Standard, Premium). |
| `php artisan db:seed --class=TenantSeeder` | Barangays + main Malaybalay users (requires plans). |
| `php artisan db:seed --class=UserSeeder` | Extra super-admin user `admin@admin` (requires tenants). |

**Recommended first-time setup on a new PC:**

```bash
php artisan migrate:fresh --seed
```

### What gets seeded (`php artisan db:seed`)

Order is defined in `database/seeders/DatabaseSeeder.php`:

1. **PlanSeeder** — Basic, Standard, Premium plans (limits, features).
2. **TenantSeeder** — Five demo barangays: Casisang, Sumpong, San Jose, Kalasungay, Caburacanan; creates `admin@malaybalay.test` and `city@malaybalay.test`.
3. **UserSeeder** — Creates `admin@admin` (super admin) and attaches to all tenants.

### Seeded login accounts

| Email | Password | Notes |
|-------|----------|--------|
| `admin@malaybalay.test` | `password` | Barangay staff — Barangay Secretary in **all five** demo barangays. Use for day-to-day barangay dashboard after selecting a barangay. |
| `city@malaybalay.test` | `password` | **Super admin** — Malaybalay City central monitoring (`/super/dashboard`, `/super/tenants`). |
| `admin@admin` | `admin` | **Super admin** — Alternative city-wide admin; also attached to all barangays as secretary. |

**Demo barangays:** Casisang, Sumpong, San Jose, Kalasungay, Caburacanan.

After login, if the user has more than one barangay, you will be asked to **select a barangay** before the main dashboard.

### Copying your real database to another PC

The SQLite file `database/database.sqlite` is **not** committed to Git (see `database/.gitignore`). To copy data manually:

1. Copy `database/database.sqlite` to the same path on the other PC, **or**
2. On the new PC, run `php artisan migrate:fresh --seed` to recreate demo data only.

For MySQL, export with `mysqldump` and import on the other machine.

---

## Usage

- **Login**: `/login` — Register at `/register` then select a barangay.
- **Dashboard**: After selecting a barangay, view stats and recent incidents. Use tenant switcher to change barangay.
- **Incidents**: List, create, edit, view incidents; attach documents; set status.
- **Mediations**: (Standard/Premium plans) Schedule mediation; assign mediator.
- **Patrol logs**: Log patrol date, time, area, activities, response details.
- **Blotter requests**: Request certified copies; staff approve or reject.
- **City admin**: `/super/dashboard` and `/super/tenants` for city-wide overview (super admin accounts only).

## Data isolation

All incident, mediation, patrol, and blotter-request data is scoped by `tenant_id`. Each barangay only sees its own data. City admin can view aggregated data across all barangays.

## License

MIT.
