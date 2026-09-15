# Asif Snooker Club — Club Management CRM

A complete, production-oriented snooker club management system for **Asif Snooker Club**,
D Ground, Faisalabad. Digitizes the handwritten daily register into a real-time CRM.

## Features

- **Table Command Center** — live visual grid of all tables with real-time timers, statuses (Available / Occupied / Reserved / Maintenance) and one-click start/end
- **Sessions & Billing** — automatic time tracking, rate calculation (hourly/frame/VIP/night), min charge, extra charges, discounts
- **Customers CRM** — profiles with visit/revenue history, **click-to-call** (`tel:`) and **WhatsApp** (`wa.me`) buttons
- **Bookings** — table availability checks, status workflow (Requested → Confirmed → Arrived → Active → Completed)
- **Payments** — Cash, **JazzCash**, Bank Transfer, Card; outstanding balance tracking
- **Expenses & Finance** — categorized expenses (Electricity, Labour, Rent, etc.) with approval tracking
- **Daily Closing** — collected by method, sessions billed, expenses, outstanding, with print & WhatsApp share
- **Analytics** — revenue by hour (peak staffing), table utilization, top customers, daily trend (7–90 day ranges)
- **Sessions history** — filterable by date range, table, payment status
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

## Default Login

Create the owner account during install (`php database/install.php`). Default seeded tables:
6 snooker tables (4 Standard @ Rs 300/hr + 2 VIP @ Rs 400/hr).

## Project Structure

```
app/
  Controllers/    HTTP request handlers
  Core/           Router, Database (PDO), Auth, Request, Response, View, Session
  Models/         Database models (User, Table, Customer, ClubSession, Booking, Payment, Expense)
  Middleware/     CSRF & auth helpers
config/           app.php, database.php, routes.php
database/         migrations/, seeders/, install.php
public/           web root — index.php, assets
resources/views/  layouts, partials, pages
storage/          logs, backups
```

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