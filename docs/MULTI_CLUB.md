# Multi-Club Readiness

This document is the **audit + blueprint** for evolving the app from a
single-club deployment (Asif Snooker Club) to one that can host **multiple
clubs** from a single codebase. The current codebase ships with no multi-tenancy
and was deliberately built to keep club identity configurable — the groundwork
below is already merged. No cross-club feature is active yet; this is a
foundation + plan, not a promise of instant multi-tenant UI.

---

## Why this is a "readiness" milestone and not multi-tenancy yet

- Every solved-for-one-club assumption is a divergence point later. This
  milestone audits those assumptions and removes the ones that are cheap to
  remove (hardcoded brand, per-view club lookups).
- True multi-tenancy touches every query, controller, migration and backup
  path. Bundling that with the current single-club product would destabilize
  the working demo. So we document the design and keep the code **single-club
  compatible** while making club identity the single source of truth.

---

## Current state (audited)

### What is already central, per club

All identity lives in `settings` (key/value, unique key):

- `club_name`, `club_phone`, `club_address`
- `currency`, `timezone`, `business_hours_*`, `night_*`, `peak_*`
- `custom_field_1..5_label`
- `cctv_server_url`, `cctv_stream_mode`, `whatsapp_template`,
  `reminder_template`, `no_show_template`, `booking_reminder_template`,
  `outstanding_reminder_template`, `reminder_enabled`, `reminder_horizon_min`,
  `default_hourly_rate`, `default_min_charge`, `accent_color`, `bookings_*`

Central accessors in `app/Services/SettingsService.php` (single source of
truth): `clubName()`, `clubAddress()`, `clubPhone()`, `whatsappNumber()`,
`whatsappTemplate()`, `whatsappContactLink()`, `telLink()` plus generic
`get()` / `set()` / `all()`.

### Work already done (merged) de-hardcoding club identity

| Location | Before | After |
|---|---|---|
| `views/partials/footer.php` | `config('app.name')` + `D Ground, Faisalabad` | `SettingsService::clubName()` · `clubAddress()` |
| `views/auth/login.php` | `config('app.name')` + hardcoded address | `clubName()`/`clubAddress()` |
| `views/layouts/blank.php`, `portal.php` | hardcoded "Asif Snooker Club" titles + footer | `clubName()`/`clubAddress()` |
| `app/Controllers/ReportsController.php` | reminder/no-show default templates had hardcoded name | template default built from `clubName()` at runtime |
| `app/Services/SettingsService.php` | whatsapp default hardcoded | default built from `clubName()` |
| `views/customers/create.php` | PIN WhatsApp text hardcoded | built from `clubName()` |
| `views/bookings/index.php` | JS booking-reminder text hardcoded | injected via `json_encode(clubName())` |

### Remaining single-club assumptions (deliberately unchanged)

- Nothing is keyed by club/branch. A deployment = one club.
- Auth is app-wide (users + roles ACL), not scoped per club.
- Backups are per-database (`database/backup.php`), and the installer creates a
  single club.
- Timezone/currency are global settings, applied by `Session::setTimezone()` /
  reporting. Multi-club would need per-club TZ/currency at render + aggregation
  time.

---

## Target design (blueprint)

### Recommended deployment model

**One vendor database per club** (separate MariaDB schema), same codebase, with
club identity configured at install. Reasons:

- Zero query-scoping surface: every existing query already runs against one
  club's tables.
- Backups (`database/backup.php`), reports, payroll, VAT/Praish calculation and
  CCTV mapping remain trivially correct per club.
- Rollout is: install wizard → pick club schema. No migration of tenant columns
  and no leakage risk between clubs.
- Cost: shared dashboard across clubs is not native; a "hubs" view is done at
  the operator level with a small aggregator (optional, later).

Alternative (single DB + `branch_id` scoping) is documented in
[Schema-scoped variant](#schema-scoped-variant-single-db--branch_id) but is NOT
the recommended path for this app.

### Naming / deployment

- Repo stays single. A `clubs.json` / operator layer maps club slugs →
  schema + install dir (or subdomain → DB), e.g. `cherry.asifsnooker.test` and
  `dground.asifsnooker.test` both served by the same app with autoselected
  `.env` / schema based on the host.
- Env-driven: each club's install gets its own `.env` (DB name, club defaults).
  The installer wizard already writes `.env` (`app/Services/InstallerService.php`).

### Where changes are required to go multi-DB

1. **Routing host → schema**: Application boot resolves `SERVER_NAME` to a club
   config (DB name, timezone). `app/core/Application` + `app/core/Database` are
   the seam.
2. **Session scope**: `Session::setTimezone()` and settings are per club — all
   good as-is since one process = one club.
3. **Portal / auth**: portal PIN auth already looks up members by phone — works
   per club schema.
4. **Backup/reminder cron**: runs per schema, schedule per club.

### Schema-scoped variant (single DB + `branch_id`)

If a single schema is ever required (shared membership ledger, cross-club
reports, one dashboard):

- New `clubs` table: `id, slug, name, address, phone, currency, timezone,
  settings_json, is_active, created_at, updated_at`.
- Add `club_id INT NOT NULL` + index to tenant tables: `tables`, `sessions`,
  `bookings`, `payments`, `expenses`, `customers`, `tournaments`, `cameras`,
  `users` (users may join multiple clubs later via `club_users`).
- Replace the `settings` table with per-club storage:
  - Option 1: `club_settings` (`club_id, key, value`) and scope every
    `SettingsService::get/set` via current club.
  - Option 2 (recommended): keep `settings` as the "current club" row cache and
    hydrate from a `settings_json` updated by SettingsController on club switch.
- Every repository/query runs through an app-level `BranchContext`
  (constant: `get('club_id')`) that injects `WHERE club_id = ?` and rejects
  queries lacking the scoping qualifier in dev (`APP_DEBUG=1`).
- Template scanning AT REQUEST TIME: `SettingsService::clubName()` already
  centralizes rendering; views that take `#club` state from the branch context
  stay club-agnostic.
- Per-club timezone/currency are applied in `Session::setTimezone()` and in the
  `#body` render path; reporting must tag rows with club_id before aggregation.

Migration order for the single-DB route:

1. Backfill `clubs` row (id=1, current name/address/phone) + add `club_id`
   columns as `NULL`, then backfill `id` and set `NOT NULL`.
2. Swap `SettingsService` to read from current club scope.
3. Add `BranchContext` and enforcement; convert models incrementally (start
   with `tables`, then bookings/sessions, then payments/expenses/reports).
4. Port `install.php` to create club + admin user.
5. Backups go per-club (file name + schema snapshot include club_id guard).

### What never needs scoping

- App code itself, roles/ACL definitions, templates/static assets, the go2rtc
  config generator (per-camera already via `cameras.stream_name`).
- `activity_logs` (if added) are cross-club at the operator level only.

---

## Rollout plan (when multi-club is requested)

1. **Phase 1 — Host-based routing** (recommended standalone DBs): Application
   boot resolves host → club `.env`; replicate current install for club #2.
   Ship a `bin/multiclub` script that copies a club dir, rewrites `.env` DB name
   + club defaults, and runs the installer/schema replicator. No tenant columns.
2. **Phase 2 — Operator hub (optional)**: a lightweight dashboard that unions
   read-only `MONEY`, `SESSIONS` across schemas for owners running >1 club.
3. **Phase 3 — Only if a shared ledger is a hard requirement**: implement the
   schema-scoped variant above, incrementally.

## Risks / guardrails

- Do NOT mix the two models (per-DB + branch_id) — pick per-DB first.
- Keep `SettingsService` as the single identity accessor; never inject a club
  name literal again. Grep for `Asif Snooker Club|D Ground|Faisalabad` in
  `app/` + `resources/` should only match installer defaults, the seeder, and
  this file.
- Backups must remain per-club; never restore club A backup into club B schema.
- Seeds: `database/seed_demo.php` creates demo identities for the *current*
  club only.