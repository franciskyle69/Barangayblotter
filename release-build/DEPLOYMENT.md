# Deployment Guide — Multi-Tenant SaaS

## Local Development

### 1. Environment Variables

```env
TENANCY_BASE_DOMAIN=localhost
TENANCY_BASE_PORT=8000
TENANCY_SUPER_SUBDOMAIN=admin
```

### 2. Testing Subdomains Locally

**Option A: Edit hosts file** (recommended for Windows)

Add to `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 casisang.localhost
127.0.0.1 sumpong.localhost
127.0.0.1 sanjose.localhost
127.0.0.1 kalasungay.localhost
127.0.0.1 caburacanan.localhost
```

Then run:

```bash
php artisan serve --host=0.0.0.0
```

Visit `http://casisang.localhost:8000` — tenant auto-resolves.

**Option B: Use `lvh.me`** (resolves to 127.0.0.1, no hosts file needed)

Set `TENANCY_BASE_DOMAIN=lvh.me` in `.env`, then visit `http://casisang.lvh.me:8000`.

### 3. Running Migrations

```bash
php artisan migrate
```

This adds `subdomain` and `custom_domain` to `tenants`, and `tenant_id` to `mediations`.

To reset with fresh demo data:

```bash
php artisan migrate:fresh --seed
```

---

## Production Deployment

### 1. Wildcard Subdomains

#### DNS

Add an A record for `*.barangayblotter.com` pointing to your server IP.

#### Nginx

```nginx
server {
    listen 80;
    server_name barangayblotter.com *.barangayblotter.com;
    root /var/www/barangayblotter/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Apache

```apache
<VirtualHost *:80>
    ServerName barangayblotter.com
    ServerAlias *.barangayblotter.com
    DocumentRoot /var/www/barangayblotter/public
    <Directory /var/www/barangayblotter/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 2. Custom Domains

Each barangay can use its own domain (e.g., `casisang.gov.ph`).

#### DNS (on the barangay's domain)

Add a CNAME record: `casisang.gov.ph → barangayblotter.com`

#### Nginx catch-all

Add a second server block that catches all other domains:

```nginx
server {
    listen 80 default_server;
    server_name _;
    root /var/www/barangayblotter/public;
    # ... same config as above
}
```

### 3. SSL Certificates

#### Wildcard SSL (for subdomains)

Use Let's Encrypt with DNS challenge:

```bash
sudo certbot certonly --dns-cloudflare \
    -d barangayblotter.com \
    -d "*.barangayblotter.com"
```

#### Custom Domain SSL

**Option A: Caddy** (automatic SSL)

```
{
    on_demand_tls {
        ask http://localhost:8080/tls-check
    }
}

:443 {
    tls {
        on_demand
    }
    root * /var/www/barangayblotter/public
    php_fastcgi unix//var/run/php/php8.2-fpm.sock
    file_server
}
```

Create an endpoint in your app at `/tls-check` that validates the domain belongs to an active tenant.

**Option B: Certbot per domain**

```bash
sudo certbot --nginx -d casisang.gov.ph
```

### 4. Environment Variables (Production)

```env
APP_URL=https://barangayblotter.com
TENANCY_BASE_DOMAIN=barangayblotter.com
TENANCY_BASE_PORT=
TENANCY_SUPER_SUBDOMAIN=admin

SESSION_DOMAIN=.barangayblotter.com
```

> **Important**: Set `SESSION_DOMAIN=.barangayblotter.com` (with leading dot) to share sessions across subdomains.

### 5. Session Configuration

For subdomains to share cookies, update `.env`:

```env
SESSION_DOMAIN=.barangayblotter.com
```

For custom domains, each domain will have its own session (this is expected and correct for data isolation).
