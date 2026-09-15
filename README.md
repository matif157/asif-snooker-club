# Asif Snooker Club — Club Management CRM

A complete, production-oriented snooker club management system for **Asif Snooker Club**,
D Ground, Faisalabad. Digitizes the handwritten daily register into a real-time CRM.

## Features

- **Table Command Center** — live visual grid of all tables with real-time timers, statuses (Available / Occupied / Reserved / Maintenance) and one-click start/end
- **Sessions & Billing** — automatic time tracking, rate calculation (hourly/frame/VIP/night), min charge, extra charges, discounts
- **Customers CRM** — profiles with visit/revenue history, **click-to-call** (`tel:`) and **WhatsApp** (`wa.me`) buttons, **CSV import & export**, per-session payment collection incl. partial
- **Bookings** — table availability checks, status workflow (Requested → Confirmed → Arrived → Active → Completed), **auto-activation** on session start/end, stale bookings auto-expire
- **Payments** — Cash, **JazzCash**, Bank Transfer, Card; outstanding balance tracking
- **Expenses & Finance** — categorized expenses (Electricity, Labour, Rent, etc.) with approval tracking
- **Daily Closing** — collected by method, sessions billed, expenses, outstanding, with print & WhatsApp share
- **Analytics** — revenue by hour (peak staffing), table utilization, top customers, daily trend (7–90 day ranges)
- **Sessions history** — filterable by date range, table, payment status
- **Peak & Night rate automation** — configurable peak/off-peak/night time bands; sessions started in a peak band auto-bill at the peak multiplier, night band when your table has a `night_rate`
- **Session e-invoices** — printable session invoices (invoice no., billed-to, line items, paid/balance-due)
- **Payment receipts** — print-ready receipts with amount in words
- **Booking calendar** — monthly grid with per-table chips and prev/next navigation
- **Follow-up & Recovery center** — outstanding customers + missed bookings with one-tap WhatsApp reminders
- **Audit log** — full action history (expense approvals, payments, sessions, etc.) with filters
- **WhatsApp Broadcast center** — audience-targeted (active / outstanding / recent / VIP) message previews with personalized links & copy-all
- **P&L report** — monthly revenue vs expenses, net profit, daily chart, method/category breakdowns + WhatsApp share
- **Customer self-service portal** (`/portal`) — public phone-number lookup showing balance, recent sessions and payments
- **Automated backups** — CLI `database/backup.php` + in-app backup manager (download/restore-ready SQL dumps, keeps last 20)
- **Notifications bell** — live alerts for full tables, today's bookings, unpaid sessions
- **Real-time updates** — live dashboard chart (real data), lightweight AJAX polling (shared-hosting friendly) + optional SSE endpoints
- **RBAC** — Owner, Admin, ECO, Counter, Staff, Auditor roles with granular permissions
- **Premium dark UI** — emerald/gold accents, responsive mobile sidebar, light/dark theme toggle

## Requirements

- PHP 8.2+ (`pdo_mysql` extension)
- MySQL 8 / MariaDB 10.4+
- Composer
- Any web server (Apache/Nginx) or the PHP built-in server

## Installation

```bash
# 1. Clone & install
git clone https://github.com/matif157/asif-snooker-club.git
cd asif-snooker-club
composer install --no-dev --optimize-autoloader

# 2. Configure environment
cp .env.example .env
#    edit .env with your DB credentials & club details

# 3. Run the installer (creates DB, tables, seed data, owner account)
php database/install.php

# 4. Serve
cd public
php -S localhost:8000
```

Then open `http://localhost:8000` and sign in with the owner account you created.

> For Apache: point `DocumentRoot` to the `public/` folder.
> For Nginx: configure root to `public/` with `index index.php` and `try_files $uri $uri/ /index.php?$query_string;`.

### Laravel Herd (macOS)

If you use [Laravel Herd](https://herd.laravel.com), the project is already linked as
a parked site at **http://asif-snooker-club.test**:

```bash
# Link (or re-link) the project manually
cd "/path/to/asif-snooker-club"
herd link              # site becomes asif-snooker-club.test
herd stop && herd start
```

- Herd serves the `public/` folder automatically (Laravel-style driver detection).
- `herd link` sets `APP_URL` in `.env` for you.
- PHP 8.4 is the recommended runtime (Herd's default `herd.sock` → `herd84.sock`).
- The bundled CLI resolver needs a `php` entry in Herd's bin — symlinked once with
  `ln -sf php84 php` inside `~/Library/Application Support/Herd/bin`.

## Default Login

Create the owner account during install (`php database/install.php`). Default seeded tables:
6 snooker tables (4 Standard @ Rs 300/hr + 2 VIP @ Rs 400/hr).

## Project Structure

```
app/
  Controllers/    HTTP request handlers (incl. PortalController for the public self-service portal)
  Core/           Router, Database (PDO), Auth, Request, Response, View, Session
  Models/         Database models (User, Table, Customer, ClubSession, Booking, Payment, Expense)
  Services/       Business logic (RateService for peak/night bands, AuditService, BackupService, SettingsService)
  Middleware/     CSRF & auth helpers
config/           app.php, database.php, routes.php
database/         migrations/, seeders/, install.php, backup.php
public/           web root — index.php, assets
resources/views/  layouts, partials, pages
storage/          logs, backups
```

## Backup & Maintenance

```bash
# Scheduled daily backup (cron-friendly)
php database/backup.php        # writes storage/backups/backup-YYYYMMDD-HHiiss.sql

# In-app
# Settings → "Create Backup Now" downloads the latest dump.
# Backups are excluded from git.
```

## Peak / Night Pricing

Configured under **Settings → Pricing & Peak Hours**:

- `peak_enabled` — toggle the surge band. `peak_start`/`peak_end` support overnight windows (e.g. `19:00` → `00:00`).
- `peak_rate_multiplier` — surge factor applied on top of the table's hourly rate during the band.
- `night_start`/`night_end` — lower night band, applies only to tables that have a `night_rate` set.
- Sessions started with `rate_type = hourly` inside a band auto-bill at the band rate; manual rates (frame/VIP/custom) are always respected.
- The live rate band and resulting rate can be previewed right inside the settings page.

## Customer Portal

Point members to `/portal` (no login needed): they enter the phone number they registered
with and instantly see their outstanding balance, last sessions and payment history.

## WhatsApp / Click-to-Call

Every customer phone auto-normalizes to Pakistan format (`03XXXXXXXXX` → `+923XXXXXXXXX`).
The CRM provides:
- **Call** button → opens the device dialer via `tel:` link
- **WhatsApp** button → opens `wa.me` chat with a pre-filled greeting from club settings

No external SMS/voice API or monthly cost required.

## Deployment

Recommended: Shared hosting (Hostinger/Bluehost etc.) with PHP 8.2 & MySQL.
Upload everything except `.env`, run `php database/install.php` via SSH or the installer,
point the domain at `public/`.

## Roadmap

- [x] Phase 1: Auth, Tables, Customers, Sessions, Bookings, Payments, Dashboard
- [x] Phase 2 (core): Expenses, Finance, Staff-ready RBAC, Reports (basic)
- [ ] Phase 3: Customer portal, notifications, theme manager, custom fields
- [ ] Phase 4: CCTV integration (RTSP → WebRTC), advanced analytics
- [ ] Phase 5: Installer wizard, backup/restore, multi-club readiness

## License

Proprietary — for Asif Snooker Club.