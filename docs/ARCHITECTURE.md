# Ulimi — Architecture (Design Record)

> Plain-PHP rebuild of the AgriVault/Farmis farm-records platform, targeting
> **biz.na.ht** free shared hosting (PHP 8.x, MySQL/MariaDB, no Composer, no
> shell, no cron, no persistent processes). Full feature parity is the goal,
> delivered in phases (see [ROADMAP.md](ROADMAP.md)).

This document is the agreed direction. It is written **before** implementation
so a wrong turn can be caught early. Nothing here is load-bearing until the
corresponding phase is built.

---

## 1. Constraints that shape every decision

| Constraint | Consequence |
|---|---|
| No Composer / no package manager on host | Small, vetted libraries are **vendored** into `/vendor` at a pinned version (PHPMailer now; JWT + PDF later). No autoloader magic — a hand-written PSR-4-ish autoloader in `app/bootstrap.php`. |
| No cron / no background workers | All "scheduled" behaviour becomes **lazy-on-read**: subscription expiry, trial-grace transitions, notification generation, weather-cache refresh are computed when a relevant page loads, deduped by key + date. |
| No shell / no CI on host | Schema is a set of **numbered `.sql` files** applied by `database/migrate.php` (run locally against the host DB, or pasted into phpMyAdmin). A `_migrations` table tracks what ran. |
| Shared `/tmp`, shared PHP session dir | **Database-backed session handler** so co-tenants on the box cannot read session files. Session cookie is `HttpOnly`, `Secure`, `SameSite=Lax`, custom name, `use_strict_mode=1`. |
| Secrets can't live in env reliably | Real secrets in `config/config.local.php` (git-ignored, outside web root). `config/config.local.php.example` is the template. |
| Host may not let us move DocumentRoot | Canonical layout points the domain at `public/`. A **fallback root `.htaccess`** denies direct access to every non-public path if DocumentRoot can't be changed. |
| Free TLS only (often via shared cert / Cloudflare) | Force HTTPS in `.htaccess` + HSTS in prod. Never assume the app terminates TLS. |
| Modest disk + upload limits | Uploaded documents capped, type-checked by magic bytes, stored outside web root, served through an authenticated PHP proxy. Large exports streamed, not buffered. |

---

## 2. Stack

- **Language:** PHP 8.1+ baseline (`declare(strict_types=1)` everywhere;
  typed properties, enums, `readonly`, `match`, nullsafe). Developed on
  WAMP PHP 8.3 + **MariaDB 11.4** (closest to a shared host; not MySQL 8.4).
- **Database:** MySQL/MariaDB, `utf8mb4`, InnoDB, real foreign keys, PDO with
  `ERRMODE_EXCEPTION` and `EMULATE_PREPARES=false`. **Prepared statements only** —
  no string-built SQL anywhere; table/column identifiers come from a whitelist.
- **Front end:** server-rendered plain-PHP templates. **Tailwind CSS v3.4**
  compiled locally (`tools/tailwind/`) to a committed `public/assets/app.css`
  — the host only ever serves static CSS. Vanilla JS for progressive
  enhancement only; every form works with JS disabled.
- **Icons:** Lucide subset baked into a self-hosted SVG sprite (`<use>`),
  no CDN, no icon font.
- **Maps:** Leaflet + leaflet-draw **self-hosted** under
  `public/assets/vendor/leaflet/` at a pinned version — keeps CSP strict.
- **Charts:** server-rendered **SVG** helpers for reports (printable, no JS).
  A tiny self-hosted plotting lib may be added later for interactive dashboard
  widgets; decided at the reporting phase.
- **Email:** vendored **PHPMailer** over SMTP (external provider — Brevo /
  Mailgun / etc.). Messages spooled to a `mail_queue` table and flushed
  opportunistically after the response (`fastcgi_finish_request()` when
  available, otherwise inline) since there is no cron.
- **Tests:** vendored **PHPUnit PHAR** (single file, no Composer). Unit tests
  for services (credit score, crop timeline, money math, validators, authz)
  and integration tests for repositories against a scratch SQLite/MariaDB.

---

## 3. Directory layout

```
ulimi-app/
  public/                     # web root — domain points here
    index.php                 # the ONLY entry point (front controller)
    .htaccess                 # rewrite → index.php; deny dotfiles; force HTTPS
    assets/
      app.css                 # compiled Tailwind (committed)
      app.js                  # progressive-enhancement JS
      sprite.svg              # Lucide icon sprite
      vendor/leaflet/…        # pinned, self-hosted
  app/
    Core/                     # tiny framework primitives
      Router.php  Request.php  Response.php  View.php
      Database.php  Session.php  Csrf.php  Validator.php
      Auth.php  Authz.php  RateLimiter.php  Mailer.php
      Config.php  Flash.php  Logger.php  ErrorHandler.php
    Support/                  # Ulid, Money, Dates, Str, Arr, Html
    Middleware/               # SecurityHeaders, Auth, AdminAuth, Csrf,
                              # FarmContext, SubscriptionLimit, RateLimit
    Models/                   # thin row objects, one per table
    Repositories/             # ALL query logic; every method farm-scoped
    Controllers/              # Auth/ Dashboard/ Farm/ Fields/ Crops/
                              # Activities/ Finance/ Livestock/ Admin/ …
    Services/                 # CreditScore, Weather, CropTimeline,
                              # ReportBuilder, Subscription, Notifications
    Views/                    # layouts/ partials/ pages/ emails/
    routes.php                # route table
    bootstrap.php             # autoloader, config, error handler, session
  config/
    config.php                # merges defaults + config.local.php
    config.local.php.example
    config.local.php          # git-ignored — real secrets
  database/
    schema/  001_core.sql  002_identity.sql  003_land.sql  …
    seed/    tiers.sql  crop_types.sql  …
    migrate.php               # applies schema/, records in _migrations
    import_live_sqlite.php    # one-off: live.sqlite → MySQL (dry-run capable)
  storage/                    # OUTSIDE public — 0700, .htaccess deny-all
    logs/  cache/  uploads/  sessions/(fallback)
  vendor/
    phpmailer/                # pinned, hand-vendored
  tools/tailwind/             # input.css, tailwind.config.js, package.json
  tests/
  docs/  ARCHITECTURE.md  ROADMAP.md  DEPLOY.md  MOBILE-API.md
  .htaccess                   # root fallback hardening (if DocumentRoot fixed)
  .gitignore
```

---

## 4. Request lifecycle

1. `public/index.php` → `require ../app/bootstrap.php`.
2. **bootstrap:** register autoloader → load config → install error/exception
   handler (prod: log + generic 500 page; dev: detailed) → start hardened
   session (DB handler) → set base security headers.
3. **Router** matches `METHOD path` against `routes.php` → resolves a
   `[Controller, action]` plus an ordered **middleware** list.
4. **Middleware stack** runs: `SecurityHeaders` → `RateLimit` (auth routes) →
   `Auth` / `AdminAuth` → `Csrf` (non-GET) → `FarmContext` → `SubscriptionLimit`
   (write routes). Any middleware may short-circuit with a `Response`.
5. **Controller** validates input via `Validator`, calls a **Repository**
   (which always receives `farmId`/`userId` from `FarmContext`, never from the
   request body), then returns a `View` render or a JSON `Response`.
6. **View** renders plain-PHP partials inside a layout via output buffering;
   `e()` HTML-escapes by default, `raw()` is explicit and rare.

---

## 5. Data model & IDs

- The full platform is **43 entities** (identity, billing, land, crops,
  activities/labour, finance, inventory, livestock, GIS, CMS, support data).
  Built table-group by table-group per the roadmap; every table carries the
  columns needed for multi-tenancy (`farm_id`, and `user_id`/`created_by_id`
  where the original has them).
- **IDs stay opaque strings** (`VARCHAR(30)`). The source `live.sqlite`
  already mixes UUIDv4, cuid, and cuid2 values; preserving them on import
  avoids a full foreign-key remap. **New** rows get a **ULID**
  (26 chars, timestamp-prefixed → good index locality, not enumerable).
- Every foreign key is a real `FOREIGN KEY … ON DELETE` constraint matching
  the Prisma `onDelete` behaviour (`CASCADE` for owned children, `RESTRICT`
  otherwise). JSON columns (`geo_json`, `permissions`, `factors`, offer items)
  use native `JSON`.
- Money is stored as `DECIMAL(14,2)` (not float) — the source uses REAL, so
  the importer rounds on the way in. All in-app money math goes through
  `Support\Money` (integer minor units).

### Migrating `live.sqlite`

One farm's real operational data ("Emmanuel Mwinama's Farm", Mchinji): 26
snake_case tables, no auth/billing/CMS/team rows. `database/import_live_sqlite.php`:

1. Creates the owner **User** (email from git config, random password →
   forced reset on first login), a **Subscription** on a default tier, and a
   **Farm** from `farm_profile`.
2. Bulk-inserts operational rows into the new schema, **preserving source IDs**,
   inside one transaction, assigning `farm_id` / `created_by_id` to the seeded
   farm/user where the source lacks them.
3. **Cleans on the way in:** dedupes `crop_types` case-insensitively (source
   has both `Maize` and `mAIZE`), normalises mixed ISO-8601 dates
   (`+02:00`, `Z`, varying precision) to UTC `DATETIME`, and detects/repairs
   mojibake (`â€"` ← `—`) with a guarded `mb_convert_encoding` pass.
4. `--dry-run` prints a row-by-row plan and a diff report; `--commit` applies.

---

## 6. Security controls — and where each one lives

Security is built into the primitives, not bolted on per feature.

| Risk (OWASP) | Control | Location |
|---|---|---|
| A03 Injection (SQL) | PDO prepared statements only; identifier whitelist; no interpolation | `Core\Database`, every Repository |
| A03 Injection (XSS) | Output-encode by default (`e()`); strict CSP (`default-src 'self'`); no `innerHTML` of user data | `Core\View`, `SecurityHeaders`, `app.js` |
| A01 Broken access control / IDOR | Deny-by-default authz; **every repository query is farm-scoped** with the context id from the session; role→permission map | `Core\Authz`, `Middleware\FarmContext`, Repositories |
| A07 Auth failures | `password_hash` (argon2id if available, else bcrypt cost 12); `session_regenerate_id` on login; generic errors + uniform timing; rate-limited | `Core\Auth`, `Middleware\RateLimit` |
| A07 Session | DB session handler; `HttpOnly`/`Secure`/`SameSite=Lax`; custom name; idle + absolute timeout; `use_strict_mode` | `Core\Session` |
| CSRF | Per-session token, `hash_equals`, required on every non-GET; SameSite cookie as depth | `Core\Csrf`, `Middleware\Csrf` |
| A02 Crypto / secrets | Secrets in `config.local.php` outside web root, git-ignored; app key for token hashing; tokens hashed at rest | `config/`, `Core\Config` |
| A05 Misconfiguration | Security headers on every response (CSP, X-Content-Type-Options, X-Frame-Options DENY, Referrer-Policy, Permissions-Policy, HSTS in prod, `X-Robots-Tag: noindex` on app/admin/api) — ported from the original `middleware.ts` | `Middleware\SecurityHeaders` |
| Unrestricted file upload | Extension + MIME allowlist, magic-byte check (`finfo`), size cap, random stored name, stored outside web root, served via authenticated proxy with `Content-Disposition: attachment` | `Services\Upload`, documents controller |
| Account enumeration | Identical responses/timing for known vs unknown email on login, register, reset | `Controllers\Auth\*` |
| Broken token flows | Activation / reset / invite tokens: 1-hour TTL, single-use, hashed at rest, constant-time compare, invalidated on use | `Core\Auth`, token repos |
| A09 Logging gaps | File logger (`storage/logs`), no PII/secrets; admin actions written to an `audit_log` table | `Core\Logger` |
| Brute force / abuse | DB-backed rate limiter on login, register, activation, reset, contact, demo | `Core\RateLimiter` |
| Admin blast radius | Separate login route, separate session namespace, separate guard, every action audit-logged | `Middleware\AdminAuth` |
| Transport | `.htaccess` HTTPS redirect + HSTS; secure cookies | `public/.htaccess`, `Core\Session` |
| Mobile API (Phase 11) | Stateless JWT (vendored), short access + refresh token, identical farm-scoping, CORS allowlist | `Controllers\Api\*` |
| Consent-based report sharing (Phase 18) | Unauthenticated route gated by a repeatable-use, hashed-at-rest token (`Support\Token`, same hashing as password/invite tokens); explicit `expires_at` + `revoked_at`; a manual per-IP rate limit on failed lookups only (the generic `Throttle` middleware exempts GETs, which doesn't fit a GET-only route); read-only, `noindex`/`no-store`, watermarked with who shared it and when | `report_share_links` table, `Controllers\Public\SharedReportController`, `Core\RateLimiter` |
| Cooperative authz (Phase 19) | Second tenant boundary alongside farms: deny-by-default (404 on a non-member's guessed/copied cooperative id), membership re-verified server-side on every request from the route, never a session or request-body value; manage-only actions (remove member, record contribution/sale) re-checked in-controller against the caller's role | `Middleware\ResolveCooperativeContext`, `Core\CooperativeContext`, `cooperative_members` |

**Honest limitation:** biz.na.ht is shared hosting. Co-tenants, no control
over the TLS layer, and DB credentials in a file on a shared box mean the
*platform* is the weakest link, not the code. This is acceptable for an
MVP / pilot with one real farm. Before onboarding paying farmers with credit
and payment data at scale, move to an isolated host (VPS/container) with
managed TLS, per-app DB users, and off-box encrypted backups. The code is
written so that move is a config change, not a rewrite.

---

## 7. Access control model (parity with the original)

- **Farm-level roles:** `owner`, `manager`, `agronomist`, `accountant`,
  `field_worker`, `viewer`. Each maps to a permission set over resource groups
  (fields, crops, activities, finance, employees, yields, reports, team,
  documents, equipment, livestock). Stored as JSON on `team_members`,
  resolved once per request into an `Authz` object; **deny by default**.
- **Multi-farm:** a user may own or be a member of several farms; a farm
  switcher sets the active `farm_id` in the session; membership is
  re-verified server-side on every request by `FarmContext`.
- **Team invites:** tokenised email invite → accept flow creates the
  `team_members` row and (if new) the `users` row.
- **Admin users** are a separate table and a separate auth domain entirely.
- **Cooperatives (Phase 19):** a second, independent tenant boundary above
  the farm — a `farm` joins a `cooperative` via `cooperative_members`
  (role: chair/secretary/treasurer/member), mirroring the `farm_members`
  shape. Unlike the farm switcher, which holds a session-based "active
  farm" a user must switch into, the active cooperative is
  resolved per-request straight from the URL (`/cooperatives/{id}/…`) by
  `Middleware\ResolveCooperativeContext`, re-verifying membership against
  `cooperative_members` every time — a cooperative id is never trusted
  from a session or the request body, same trust model as `FarmContext`.
  A farm can belong to several cooperatives at once, so there is no
  single "active" one to hold in session the way there is for farms.
  Cooperative-scoped data (contributions, collective sales + per-member
  splits) is new storage; the group inventory/production rollup
  (`Services\CooperativeStats`) is a read-only aggregate over each member
  farm's existing tables, not a copy. Joining is by a shared `join_code`,
  no approval step — the simplest thing that lets a real group form.
- **Report share links (Phase 18):** the one deliberately unauthenticated
  read path in the app (`Controllers\Public\SharedReportController`,
  `/shared/reports/{token}`). Gated on `reports.manage` — which, per the
  role matrix above, only `owner` actually holds; `manager` gets every
  other resource's `.manage` but not `reports`, a deliberate restriction
  given this is the one action that hands farm data to someone outside
  the farm. The token (`Support\Token`, 256 bits) is repeatable-use, not
  single-use like activation/reset tokens — a lender may reopen the same
  link several times before it expires (14 days) or the farmer revokes it.
  The viewer's identity is never checked; trust comes entirely from
  possessing the token, exactly like sharing any other secret link. Every
  other write/read in the app still requires session auth — this is
  additive, not a weakening of the model above.

- **Mobile API:** the same `Authz`/`FarmContext` model, but resolved per-request from
  a JWT (`Middleware\AuthenticateApi`) and an `X-Farm-Id` header
  (`Middleware\ResolveFarmContextApi`) instead of a session — no server-side session
  exists for API clients. Full endpoint reference, auth flow, and the offline
  sync-batch contract: [MOBILE-API.md](MOBILE-API.md).

## 8. Billing / subscription logic (parity)

- **Tiers** carry per-resource limits (`max_fields`, `max_crops`,
  `max_activities`, `max_transactions`, `max_employees`, `max_farms`,
  `max_team_members`; `-1` = unlimited) and feature flags (season analytics,
  yield suggestions, cost/ha, payroll, multi-farm, team accounts, custom
  reports, API access, sync).
- `Middleware\SubscriptionLimit` guards **write** routes: it counts current
  usage for the active farm/account and blocks creation past the cap with a
  clear upgrade message. Feature-flagged pages check the flag in the
  controller.
- **Expiry without cron:** on login and dashboard load, `Services\Subscription`
  compares `end_date` / `trial_ends_at` to now and transitions
  `active → past_due → view-only grace → expired` lazily, writing the new
  status once.
- Payments are recorded (cash / mobile money / bank transfer / card / paypal
  reference). No card data is ever stored or entered in-app.

## 9. What deliberately differs from the original

| Original | Ulimi (plain PHP) | Why |
|---|---|---|
| NextAuth sessions | PHP session + `password_hash`, DB session store | No Node; avoid shared session dir |
| Hand-rolled mobile JWT | Vendored JWT lib, same claims | Don't hand-roll token crypto |
| Nightly jobs (expiry, notifications) | Lazy-on-read with dedupe keys | No cron |
| Recharts (React) | Server-rendered SVG + optional tiny JS lib | No build step on host; printable reports |
| Prisma migrations | Numbered `.sql` + `migrate.php` + `_migrations` | No Composer/CLI on host |
| Tailwind v4 runtime pipeline | Tailwind v3.4 compiled locally, CSS committed | Host serves static files only |
| CDN assets (Leaflet, fonts, icons) | All self-hosted | Strict CSP, offline-friendly, no third-party calls |

---

## 10. Local dev & deploy

- **Local:** WAMP — Apache 2.4, PHP 8.3, MariaDB 11.4. Vhost points at
  `public/`. `config/config.local.php` holds local DB + a dev SMTP (Mailtrap).
- **Schema:** `php database/migrate.php` applies `schema/` then `seed/`.
- **Import:** `php database/import_live_sqlite.php --dry-run` then `--commit`.
- **CSS:** `cd tools/tailwind && npm i && npm run build` → commits
  `public/assets/app.css`. (Node is a *local* convenience only.)
- **Deploy to biz.na.ht:** upload the tree (SFTP/file manager), point the
  domain at `public/` (or drop the fallback `.htaccess`), create the MySQL DB
  in the host panel, run `migrate.php` against it (locally with the host DB
  credentials, or paste SQL into phpMyAdmin), fill `config/config.local.php`
  on the server, set `storage/` to `0700`. Full runbook in `DEPLOY.md`
  (written in Phase 1).
