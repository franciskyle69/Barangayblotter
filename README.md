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

## Setup on a New PC (Step-by-Step)

Follow these steps on any new machine to get the Barangay Blotter running. It's easy!

### Prerequisites

Before you start, make sure you have these installed:

- **PHP 8.2+** - [Download](https://www.php.net/downloads)
- **Composer** - [Download](https://getcomposer.org/download/)
- **Node.js & npm** - [Download](https://nodejs.org/)
- **Git** (optional, for cloning) - [Download](https://git-scm.com/)

Check if they're installed:

```bash
php --version
composer --version
node --version
npm --version
```

### Step 1: Get the Code

**Option A - Clone from GitHub:**

```bash
git clone https://github.com/franciskyle69/Barangayblotter.git
cd Barangayblotter
```

**Option B - Copy from existing installation:**

1. Copy the entire project folder to your new PC
2. Open PowerShell/Terminal in that folder

### Step 2: Install Dependencies

**PHP packages:**

```bash
composer install
```

**JavaScript packages:**

```bash
npm install
```

### Step 3: Setup Environment File

Copy the example environment file:

```bash
copy .env.example .env
```

Or on macOS/Linux:

```bash
cp .env.example .env
```

Generate the app key:

```bash
php artisan key:generate
```

**The `.env` is already configured for SQLite (default).** No further editing needed unless you want to use MySQL.

### Step 4: Create the Database

**For SQLite (recommended for development):**

Windows PowerShell:

```bash
New-Item -ItemType File -Path database\database.sqlite -Force
```

macOS/Linux:

```bash
touch database/database.sqlite
```

**For MySQL instead:**

1. Edit `.env` and change `DB_CONNECTION=mysql`
2. Set your database credentials:
    ```env
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=barangay_blotter
    DB_USERNAME=root
    DB_PASSWORD=your_password
    ```
3. Create the database in MySQL first:
    ```sql
    CREATE DATABASE barangay_blotter;
    ```

### Step 5: Migrate Database & Load Demo Data

This creates all tables and loads sample barangays, plans, and users:

```bash
php artisan migrate:fresh --seed
```

This will create:

- ✅ 5 sample barangays (Casisang, Sumpong, San Jose, Kalasungay, Caburacanan)
- ✅ 3 subscription plans (Basic, Standard, Premium)
- ✅ Demo login accounts (see **Demo Accounts** below)

### Step 6: Setup File Storage

Create a public link for file uploads:

```bash
php artisan storage:link
```

### Step 7: Build Frontend Assets

Build the React/Vite frontend:

```bash
npm run build
```

For **development with live reload** (keep this running in a separate terminal):

```bash
npm run dev
```

### Step 8: Start the Server

In a new terminal, run:

```bash
php artisan serve
```

You'll see:

```
   INFO  Server running on [http://127.0.0.1:8000].
```

Open **http://127.0.0.1:8000** in your browser. 🎉

---

## Demo Accounts (After Setup)

After running `php artisan migrate:fresh --seed`, use these accounts to log in:

### Login Credentials

| Email                   | Password   | Role               | Purpose                                                              |
| ----------------------- | ---------- | ------------------ | -------------------------------------------------------------------- |
| `admin@malaybalay.test` | `password` | Barangay Secretary | Day-to-day barangay staff work. Available in **all demo barangays**. |
| `city@malaybalay.test`  | `password` | Super Admin        | City-wide monitoring dashboard. View all barangays.                  |
| `admin@admin`           | `admin`    | Super Admin        | Alternative city admin account. Also a secretary in all barangays.   |

### First Login Steps

1. Go to **http://127.0.0.1:8000/login**
2. Enter email and password from the table above
3. You'll be asked to **select a barangay**
4. Choose one of the 5 demo barangays:
    - Casisang
    - Sumpong
    - San Jose
    - Kalasungay
    - Caburacanan
5. Click the barangay to enter the dashboard 🎉

### What You Can Do Now

- **Report Incidents**: Click "Report an Incident" to create a new incident report
- **View Incidents**: See all incidents for your barangay with status filters
- **Request Blotter Copies**: Request certified copies of incident reports
- **Check Dashboard**: View statistics and recent activities
- **Staff Functions** (if logged in as staff): Edit incidents, schedule mediations, manage patrol logs

---

## Advanced: Seeding Commands

If you need to reset or reseed data, use these commands:

| Command                                    | What it does                                                               |
| ------------------------------------------ | -------------------------------------------------------------------------- |
| `php artisan migrate:fresh --seed`         | **Wipes everything and starts fresh.** Use for a clean slate on new PC.    |
| `php artisan db:seed`                      | Adds seed data to existing database (may cause duplicates if data exists). |
| `php artisan db:seed --class=PlanSeeder`   | Only seed plans (Basic, Standard, Premium).                                |
| `php artisan db:seed --class=TenantSeeder` | Only seed barangays and main users (requires plans).                       |

**For a new PC, always use:**

```bash
php artisan migrate:fresh --seed
```

---

## Moving Your Database to Another PC

If you already have data in your local database and want to transfer it to another PC, follow these steps:

### SQLite (Recommended - Easiest)

SQLite stores everything in a single file: `database/database.sqlite`

**Step 1: Copy the database file**

On your **original PC**, locate the file at:
```
database/database.sqlite
```

Copy this file to a USB drive or cloud storage (Google Drive, Dropbox, etc.).

**Step 2: Paste on the new PC**

On the **new PC**, after you've done Steps 1-6 of the setup guide:

1. Delete the empty database file:
   ```bash
   # Windows
   Remove-Item database\database.sqlite
   
   # macOS/Linux
   rm database/database.sqlite
   ```

2. Paste the copied `database.sqlite` file into the `database/` folder

3. Done! Your data is now on the new PC. Start the server:
   ```bash
   php artisan serve
   ```

### MySQL/MariaDB (More Complex)

If you're using MySQL instead of SQLite, here's how to move the database:

**Step 1: Export on the original PC**

On your **original PC**, export the database using `mysqldump`:

```bash
mysqldump -u root -p barangay_blotter > database_backup.sql
```

You'll be asked for your MySQL password. This creates a `database_backup.sql` file.

Copy this file to a USB drive or cloud storage.

**Step 2: Import on the new PC**

On the **new PC**, after setting up MySQL with the same credentials:

```bash
mysql -u root -p barangay_blotter < database_backup.sql
```

Enter your MySQL password when prompted.

**Step 3: Verify the connection**

Make sure your `.env` file on the new PC has the correct MySQL credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barangay_blotter
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then start the server:
```bash
php artisan serve
```

### Which Database Type Am I Using?

Check your `.env` file:
- If it says `DB_CONNECTION=sqlite` → You're using SQLite (just copy the `.sqlite` file)
- If it says `DB_CONNECTION=mysql` → You're using MySQL (use mysqldump export/import)

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
