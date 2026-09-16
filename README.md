# Asif Snooker Club — Club Management CRM

A complete, production-oriented snooker club management system for **Asif Snooker Club**,
D Ground, Faisalabad. Digitizes the handwritten daily register into a real-time CRM.

## Features

- **Table Command Center** — live visual grid of all tables with real-time timers, statuses (Available / Occupied / Reserved / Maintenance) and one-click start/end
- **Operational dashboard** — KPI cards with day-over-day deltas (revenue, sessions, tables, profit), this-week vs last-week strip, **Needs Attention** alerts (unpaid sessions, arriving bookings, long-running tables, maintenance, **member booking requests awaiting approval**, tables with a live camera shortcut), payments-by-method donut, top tables today, and one-click quick actions (start session / booking / payment / expense)
- **Sessions & Billing** — automatic time tracking, rate calculation (hourly/frame/VIP/night), min charge, extra charges, discounts
- **Customers CRM** — profiles with visit/revenue history, **click-to-call** (`tel:`) and **WhatsApp** (`wa.me`) buttons, **CSV import & export**, per-session payment collection incl. partial
- **Bookings** — table availability checks, status workflow (Requested → Confirmed → Arrived → Active → Completed), **auto-activation** on session start/end, stale bookings auto-expire, **advance deposit at booking** and **"pay later"** record/approval from the bookings page
- **Payments** — Cash, **JazzCash** (with transaction reference), Bank Transfer, Card; advance deposits and later payments are recorded against the booking, outstanding balance tracking; printable receipts
- **Expenses & Finance** — categorized expenses (Electricity, Labour, Rent, etc.) with approval tracking
- **Daily Closing** — collected by method, sessions billed, expenses, outstanding, with print & WhatsApp share
- **Analytics** — revenue by hour (peak staffing), table utilization, top customers, daily revenue vs expenses, sessions-by-hour, category & booking-status breakdowns (7–90 day ranges)
- **Sessions history** — filterable by date range, table, payment status
- **WhatsApp reminders** — scheduled booking & outstanding-balance reminders written to `/reminders` center + auto-batched by hourly cron (`database/remind.php --run`), zero API cost via dedicated wa.me links
- **Customer custom fields** — up to 5 configurable profile fields (labels + per-customer values, Settings → Customer Fields)
- **Peak & Night rate automation** — configurable peak/off-peak/night time bands; sessions started in a peak band auto-bill at the peak multiplier, night band when your table has a `night_rate`
- **Session e-invoices** — printable session invoices (invoice no., billed-to, line items, paid/balance-due)
- **Payment receipts** — print-ready receipts with amount in words
- **Booking calendar** — monthly grid with per-table chips and prev/next navigation
- **Follow-up & Recovery center** — outstanding customers + missed bookings with one-tap WhatsApp reminders
- **Audit log** — full action history (expense approvals, payments, sessions, etc.) with filters
- **WhatsApp Broadcast center** — audience-targeted (active / outstanding / recent / VIP) message previews with personalized links & copy-all
- **P&L report** — monthly revenue vs expenses, net profit, daily chart, method/category breakdowns + WhatsApp share
- **Customer self-service portal** (`/portal`) — PIN-protected member dashboard: balance, **table booking requests**, upcoming bookings, recent sessions and payments
- **Automated backups** — CLI `database/backup.php` + in-app backup manager (download/restore-ready SQL dumps, keeps last 20)
- **Notifications bell** — live alerts for full tables, today's bookings, unpaid sessions
- **Real-time updates** — live dashboard chart (real data), KPI auto-polling every 15s, lightweight AJAX polling (shared-hosting friendly) + optional SSE endpoints
- **RBAC** — Owner, Admin, ECO, Counter, Staff, Auditor roles with granular permissions, editable per-role permission matrix (Owner/Admin locked full-access) including CCTV view/manage
- **CCTV live grid** — `/cctv` browser-based live camera wall fed by a local media server (go2rtc/mediamtx); camera registry with name, location, RTSP source and stream names, **each camera assignable to a table** (shown as a badge on the tile and as a shortcut on the dashboard table grid), edit-in-place from the wall, enabled/disabled per camera
- **Visual customizer** — club accent colour (swatches + custom picker) flows through buttons, badges, nav, charts; per-user **Dark / Light / Auto** theme persisted server-side
- **Premium dark UI** — responsive sidebar, notifications bell, snooker-branded login (D Ground, Faisalabad)

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

## Customization — Roles & Themes

**Roles & Permissions** — Settings → Roles & Permissions. Every non-superuser role
(ECO, Counter, Staff, Auditor) has a full checkbox matrix over all 20 permissions
(e.g. `tables.manage`, `reports.view`, `settings.manage`, `cctv.view`). Save is transactional and
audited; **Owner & Admin always bypass the matrix (full access)** so you can't lock
yourself out. The current user's own `settings.manage` is force-kept.

**Themes** — Settings → Appearance:
- **Accent colour**: pick a preset (Emerald, Baize, Violet, Sky, Rose, Gold) or a custom
  colour. It is stored in `settings.accent_color` and drives the whole `emerald` palette
  (buttons, badges, nav, focus rings, charts) via CSS variables — no page refresh needed.
- **Theme mode**: per-user **Dark / Light / Auto** (Auto follows the OS), persisted on
  the user's account via `POST /theme`. The header toggle switches and saves instantly.

## Customer Portal

Point members to `/portal` (no login needed): they enter the phone number they registered
with **and the 4-digit portal PIN**. On success they land on their member dashboard showing
their outstanding balance, recent sessions and payment history, plus a **Book a Table** form
(pick a table, date and time window — club checks availability). Bookings created there appear
in the CRM as **Requested** with a "Portal" badge, so the counter person can review and
approve/decline them; the member sees the live status on their dashboard.

Setting the PIN: from the customer's edit page (Portal Access), type a new 4-digit PIN — the
CRM stores it hashed and provides a one-tap **WhatsApp link** that sends the member their PIN
along with the `/portal` address. Only the phone number + correct PIN can sign in; failed
attempts show an error and the member stays on the PIN screen.

## WhatsApp / Click-to-Call

Every customer phone auto-normalizes to Pakistan format (`03XXXXXXXXX` → `+923XXXXXXXXX`).
The CRM provides:
- **Call** button → opens the device dialer via `tel:` link
- **WhatsApp** button → opens `wa.me` chat with a pre-filled greeting from club settings

No external SMS/voice API or monthly cost required.

## CCTV / Live Camera Wall

The `/cctv` page shows your camera feeds as a no-plugin browser grid. It does **not**
process video itself — a tiny local media server restreams your IP cameras as HTTP; the CRM
just displays them.

Setup:

1. Install [go2rtc](https://github.com/AlexxIT/go2rtc) (or [mediamtx](https://github.com/bluenviron/mediamtx)) on a box that can reach the cameras.
2. Define each camera in its config, e.g.:

   ```yaml
   streams:
     table01: rtsp://admin:pass@192.168.1.20:554/stream1
     tables_all: rtsp://admin:pass@192.168.1.21:554/stream1
   ```

3. In the CRM: **Add Camera** with the same **Stream name** (`table01`, …), optionally the RTSP source, a friendly name + location, and an **Assigned Table** (optional — the table's number/name shows on the tile and a camera shortcut appears on that table in the dashboard command center). Enabled cameras appear in the grid as live `<img>` tiles; missing/offline streams show a "No signal" placeholder. Cameras can be edited straight from the wall (rename, re-assign table, enable/disable).
4. If go2rtc runs on another machine, set its address in **Settings → CCTV** (default `http://127.0.0.1:1984`).

Roles with `cctv.view` see the wall; `cctv.manage` (Owner/Admin) can add/remove cameras.

## Deployment

Recommended: Shared hosting (Hostinger/Bluehost etc.) with PHP 8.2 & MySQL.
Upload everything except `.env`, run `php database/install.php` via SSH or the installer,
point the domain at `public/`.

## Roadmap

- [x] Phase 1: Auth, Tables, Customers, Sessions, Bookings, Payments, Dashboard
- [x] Phase 2 (core): Expenses, Finance, Staff-ready RBAC, Reports (basic)
- [x] Phase 3: Customer portal, notifications bell, theme manager (accent + per-user theme), role-permission manager
- [ ] Phase 4: CCTV integration (RTSP → WebRTC), advanced analytics, custom fields
- [ ] Phase 5: Installer wizard, backup/restore, multi-club readiness

## License

Proprietary — for Asif Snooker Club.