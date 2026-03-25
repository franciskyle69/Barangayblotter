# START HERE - 3 Commands to Run the System

## 🚀 The Absolute Minimum

If you have already run setup once, just do this:

```powershell
# Terminal 1
cd c:\Users\Francis\Desktop\blotter\Barangayblotter
php artisan serve
```

```powershell
# Terminal 2 (optional for hot reload, but recommended)
cd c:\Users\Francis\Desktop\blotter\Barangayblotter
npm run dev
```

**Then open**: http://127.0.0.1:8000

**Login**: `admin@malaybalay.test` / `password`

---

## 🔧 First Time Setup? Do This Once:

```powershell
cd c:\Users\Francis\Desktop\blotter\Barangayblotter
composer install
npm install
copy .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan migrate:fresh --seed
php artisan storage:link
npm run build
```

Then follow the "Absolute Minimum" above.

---

## 🌐 Access

| URL | Purpose |
|-----|---------|
| http://127.0.0.1:8000 | Main application |
| http://127.0.0.1:8000/login | Login page |
| http://127.0.0.1:8000/super/dashboard | City admin dashboard |
| http://localhost:5173 | Vite dev server (if running) |

---

## 📝 Demo Accounts

```
Barangay Staff:
  Email: admin@malaybalay.test
  Password: password

City Admin:
  Email: city@malaybalay.test
  Password: password

Super Admin:
  Email: admin@admin
  Password: admin
```

---

## ⚠️ Terminal Issues?

**Laravel won't start?**
```powershell
php artisan serve --port=8001
```

**npm not found?**
- Install Node.js from https://nodejs.org/

**PHP not found?**
- Install PHP 8.2+ or add to Windows PATH

**Database errors?**
```powershell
php artisan migrate:fresh --seed
```

---

**That's it! You're ready to go.** 🎉
