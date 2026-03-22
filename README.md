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
- Node.js & npm (for Vite/Tailwind)
- MySQL / MariaDB or SQLite

## Installation

1. **Clone and install PHP dependencies**
   ```bash
   cd multi
   composer install
   ```

2. **Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Edit `.env`: set `APP_NAME="Malaybalay City Barangay Blotter"`, and `DB_*` (e.g. SQLite or MySQL).

3. **Migrations and seed**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
   For a fresh install with only Malaybalay City data: `php artisan migrate:fresh --seed`

4. **Storage link (for incident attachments)**
   ```bash
   php artisan storage:link
   ```

5. **Frontend (optional)**
   ```bash
   npm install
   npm run build
   ```

## Seeded accounts (Malaybalay City)

| Email | Password | Role |
|-------|----------|------|
| admin@malaybalay.test | password | Barangay Secretary (all 5 demo barangays) |
| city@malaybalay.test | password | Malaybalay City Admin (central monitoring) |

Demo barangays: **Casisang**, **Sumpong**, **San Jose**, **Kalasungay**, **Caburacanan** (South Highway, North Highway, Upper Pulangi districts).

## Usage

- **Login**: `/login` — Register at `/register` then select a barangay.
- **Dashboard**: After selecting a barangay, view stats and recent incidents. Use “Switch Barangay” to change tenant.
- **Incidents**: List, create, edit, view incidents; attach documents; set status.
- **Mediations**: (Standard/Premium) Schedule mediation; assign mediator.
- **Patrol logs**: Log patrol date, time, area, activities, response details.
- **Blotter requests**: Request certified copies; secretary/captain approve or reject.
- **City admin**: `/super/dashboard` and `/super/tenants` for Malaybalay City–wide overview.

## Data isolation

All incident, mediation, patrol, and blotter-request data is scoped by `tenant_id`. Each barangay only sees its own data. City admin can view aggregated data across all barangays.

## License

MIT.
