# Asif Snooker Club — Deployment Runbook

Production-oriented, step-by-step guide for putting the CRM online.

---

## 1. Requirements

- **Web server**: Apache (mod_rewrite) or Nginx — document root must point at `public/`
- **PHP**: 8.2+ with `pdo_mysql`, `mbstring`, `json`, `openssl`
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Composer** on the machine that builds the release
- Extras (optional): a cron runner (`remind.php`, `backup.php`), git

> The app needs **zero** external services at runtime — no API keys required.
> WhatsApp links are click-to-chat; background notifications only need a cron.

---

## 2. Build the release tarball

Run on a machine with Composer (or directly on the server):

```bash
git clone https://github.com/matif157/asif-snooker-club.git asif-snooker-club
cd asif-snooker-club
composer install --no-dev --optimize-autoloader

# Strip anything not needed at runtime
rm -rf .git storage/logs/*
```

Everything except `.env` and `storage/` stays in the archive.

---

## 3. Upload

Upload the folder to the host, e.g. under `~/asif-snooker-club`. Transfer
`storage/` too (or recreate it) so backups/logs have a place:

```bash
mkdir -p storage/backups storage/logs
```

**Never** place `.env` under the web root — keep it one level above `public/`.

---

## 4. Configure

### `.env`

```ini
APP_NAME="Asif Snooker Club"
APP_ENV=production
APP_DEBUG=false          # MUST be false in production
APP_URL=https://crm.example.com
APP_TIMEZONE=Asia/Karachi
APP_CURRENCY=Rs

DB_HOST=localhost         # or the host's MySQL host
DB_PORT=3306
DB_NAME=asif_snooker_club
DB_USER=asif_crm
DB_PASS='a-strong-password'
```

### Apache document root

Point the domain (or subdomain) at `<app>/public`. With `.htaccess` already present
the app works out of the box in common cPanel/LiteSpeed setups:

```apache
# vhost
DocumentRoot /home/user/asif-snooker-club/public
<Directory /home/user/asif-snooker-club/public>
    AllowOverride All
    Require all granted
</Directory>
```

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name crm.example.com;
    root /srv/asif-snooker-club/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

---

## 5. Database

Two ways, both produce an identical schema (migrations `001`–`008` are applied
automatically by the install path).

### A. Web installer (easiest)

Open `https://crm.example.com/install`, fill in DB + admin, done.
`/install` becomes unreachable once the app is installed.

### B. CLI

```bash
php database/install.php        # reads DB_* and ADMIN_* from .env
```

Then verify permissions/permissions table seeded. Default role matrix is seeded by
`database/seeders/default_data.sql`.

---

## 6. First-login checks

- Sign in as the admin created during install.
- **Settings** → club name, phone, currency, business hours.
- **Roles** — confirm the seeded owner/admin/eco/counter permissions are correct for you.
- Create staff accounts from **Staff**.
- **Tables** — 7 seeded demo tables exist; rename/re-price to match the club floor.
- **Cameras** — add per-table / wall cameras on the CCTV page (URL used by the
  browser, e.g. JPEG/RTSP proxy or HLS).

---

## 7. TLS / HTTPS

- Enable SSL on the domain (Let's Encrypt / host's panel).
- Set `APP_URL` to the `https://` URL.
- The app sends `Strict-Transport-Security` automatically when served over HTTPS
  (and when behind a proxy that sets `X-Forwarded-Proto: https`).

---

## 8. Background jobs (cron)

```cron
# Nightly bookmark reminders (02:00)
0 2 * * * /usr/bin/php /srv/asif-snooker-club/database/remind.php

# Nightly database backup (02:30)
30 2 * * * /usr/bin/php /srv/asif-snooker-club/database/backup.php

# Ping WhatsApp/notify scripts if you want any additional integrations
```

Backups land in `storage/backups/`; pull them off-server periodically (rsync/S3).

---

## 9. Production checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` is the real `https://` domain
- [ ] DB password is strong, app DB user has only its own schema privileges
- [ ] `.env` is outside `public/` and chmod 600
- [ ] `public/` is the only web-served directory
- [ ] Backups are scheduled and copied off-server
- [ ] Login throttle active (built-in): 5 failed attempts → 15 min lockout per session
- [ ] Security headers confirmed:
      `curl -sI https://crm.example.com/ | grep -iE 'content-security|strict-transport|x-frame'`

---

## 10. Local development (Herd) + public preview via ngrok

Local: serve the project from Herd, open `http://asif-snooker-club.test`.

Public tunnel (two options):

```bash
# A: PHP built-in server + ngrok
php -S 0.0.0.0:8080 -t public public/index.php
ngrok http 8080

# B: advertise the site's own host header so routing stays intact
ngrok http --host-header=asif-snooker-club.test 80
```

Open the `*.ngrok-free.app` URL. Links/redirects/session cookies adapt to the
forwarded host automatically.

---

## 11. Troubleshooting

| Symptom | Likely fix |
| --- | --- |
| Blank page / 500 with `APP_DEBUG=false` | Check `storage/logs/app-YYYY-MM-DD.log`; flip `APP_DEBUG=true` briefly to see the message |
| Assets 404 | Document root must be `public/`, not the project root |
| Gate to `/install` after setup | Installer redirects to login once `migrations`/settings exist — re-running won't reinstall |
| Logged out on every page | Session cookie `Secure` flag requires HTTPS; make sure `APP_URL` is `https://`, or no mixed content |
| Login keeps failing | 5-attempt throttle: wait 15 min or clear the browser's session cookie |
| Links point to `localhost` | `APP_URL` is wrong in `.env` |
| ngrok page loads but forms 404 | Use option B (`--host-header`) or the built-in server option A |