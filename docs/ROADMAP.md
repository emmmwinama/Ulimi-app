# Ulimi — Delivery Roadmap

Full parity with AgriVault/Farmis, built in dependency order. Each phase is a
self-contained, testable increment: schema slice + repositories + controllers +
views + tests + a security-review note. See [ARCHITECTURE.md](ARCHITECTURE.md).

Legend: ☐ not started · ◐ in progress · ☑ done

---

## Phase 1 — Foundation & Identity  ☑
The skeleton everything else hangs off.

**Done.** Core runtime, middleware pipeline, DB-backed sessions, CSRF, rate
limiting, argon2id auth (customer + separate admin), role/permission Authz,
lazy subscription reconciliation, `migrate.php` + `001_core`/`002_identity` +
tier seed, full auth flows (register→activate→onboarding, login/logout,
forgot/reset, change password), team invite/accept/roles, farm switcher,
account settings, admin login + overview, hand-authored design system, error
pages, `make_admin.php`, 17 passing unit tests, `DEPLOY.md`.
_Deferred to end of Phase 5:_ `import_live_sqlite.php` (needs the operational
tables from Phases 2–5 to exist first).
_Deviation:_ CSS is a hand-authored system, not Tailwind output — no Node
runtime is available in the build environment. ID columns are `VARCHAR(40)`
(not 30) to fit legacy UUIDs.

- Project structure, autoloader, `bootstrap.php`, front controller, `.htaccess`
- Core primitives: Router, Request, Response, View, Database, Session (DB
  handler), Csrf, Validator, Auth, Authz, RateLimiter, Mailer, Config, Flash,
  Logger, ErrorHandler
- Middleware: SecurityHeaders, Auth, AdminAuth, Csrf, FarmContext, RateLimit
- Schema `001_core` + `002_identity`: `_migrations`, `sessions`, `rate_limits`,
  `mail_queue`, `audit_log`, `users`, `admin_users`, `password_tokens`,
  `farms`, `farm_members` (roles + JSON permissions), `subscription_tiers`,
  `subscriptions`, `payments`
- Auth flows: register (with tier choice), login, logout, email activation,
  forgot/reset password, change password — all rate-limited, enumeration-safe
- Farm context + farm switcher; team invite + accept; role/permission gate
- Admin: separate login, dashboard shell
- App shell: layout, sidebar nav, top bar, flash messages, the Ulimi design
  system (tokens, buttons, forms, tables, cards, empty states), icon sprite
- Public: minimal landing page + legal page stubs
- `database/migrate.php`, `database/import_live_sqlite.php` (dry-run + commit),
  seed tiers + canonical crop types
- `DEPLOY.md` runbook for biz.na.ht
- Tests: authz matrix, csrf, validators, token lifecycle, rate limiter

## Phase 2 — Land & Crops  ☑
- Schema `003_land` + `004_crops`: `fields`, `crop_types`, `crop_fields`
  (+ archive), plus `seasons` derivation
- Fields CRUD (area, soil, GPS, notes)
- Crop types registry (+ custom, deduped)
- Crop plantings CRUD, archive with reason, status lifecycle, timeline view
- `Services\CropTimeline` — stage guidance for the ~20 supported crops,
  due/overdue derivation
- Dashboard v1: farm summary tiles (fields, area, active crops, workers)
- Import maps `live.sqlite` fields / crop_fields / crop_types

## Phase 3 — Activities, Labour & Yields  ☑
- Schema `005_activities`: `farm_activities`, `activity_labour`,
  `activity_inputs`, `activity_other_costs`, `employees`, `harvest_yields`
- Activity log CRUD with nested labour / inputs / other costs
- Employee / payroll roster CRUD (payroll gated by tier flag)
- Harvest yield records + unit-weight normalisation
- Activity-type suggestions from `CropTimeline`
- Import maps activities + children, employees, yields

## Phase 4 — Finance & Inventory  ☑
- Schema `006_finance` + `007_inventory`: `transactions`,
  `overhead_expenses`, `inventory_items`, `inventory_sales`,
  `inventory_sale_links`
- Income/expense transactions, season tagging, field/crop linkage
- Overhead expenses (recurring flag, categories)
- Inventory: harvest-sourced + manual stock, sales, sale→transaction links
- Inventory-costed activity inputs (acquisition cost + 12%/yr carrying charge,
  auto stock decrement)
- Season profitability + cost-per-ha / per-kg (gated by tier flags)
- Import maps transactions, overhead, inventory, inventory_sales

## Phase 5 — Livestock  ☑
- Schema `008_livestock`: `livestock_types`, `animals`, `animal_health`,
  `animal_production`, `animal_weight`, `animal_expenses`, `animal_sales`
- Full lifecycle CRUD + parent/offspring links
- Livestock stats (headcount by type, production, cost, sales)
- Import maps all `animal_*` tables

## Phase 6 — GIS / Field Mapping  ☑
- Schema `009_gis`: `field_boundaries`, `field_zones`, `farm_markers`
- Self-hosted Leaflet + leaflet-draw
- Per-field boundary drawing + zone editing; farm-wide map; markers
  (borehole, irrigation, shed, road, gate, other); satellite toggle
- Server-side polygon area (hectares) + centroid
- Import maps boundaries / zones / markers (GeoJSON preserved)

## Phase 7 — Reporting, Compliance & Credit  ☑
- `Services\ReportBuilder` — sections/columns/filters → CSV + hand-built PDF
  (vendored FPDF), brand-compliant cover + footers
- Canned reports: cashflow, crop/field profitability, cost per ha/kg, trends
- Record packs (loan / buyer / audit / insurance) with section filters
- `Services\Traceability` — lot IDs, buyer-ready checklist scoring
- `Services\CreditScore` — cashflow / activity-cost / revenue factors, grade
- Compliance checklist (evidence-gap view)

## Phase 8 — Ancillary Services  ☐
- Schema `010_support`: `market_prices`, `weather_cache`, `farm_documents`,
  `notifications`, `farm_credit_scores`, seasonal `templates`
- Weather: Open-Meteo, 3-hour DB cache, farm-location aware
- Market prices + farmer-facing comparison page (fills an original gap)
- Documents: hardened upload + authenticated proxy download + linking
- Notifications: lazy generation (harvest due, no activity, low inventory,
  price alert), inbox, read state
- Seasonal templates (activity / payroll / sales) by crop
- Settings (farm, account, password, danger zone)

## Phase 9 — Admin Back Office  ☐
- Schema `011_cms`: `site_content`, `cms_pages`, `cms_features`, `cms_media`,
  `testimonials`, `contact_submissions`, `demo_bookings`
- Admin: users (+ activation), tiers, subscriptions (+ lazy auto-expiry),
  payments, inquiries, testimonials, market data, CMS pages/features/media
- Enforce `SubscriptionLimit` across every write path + feature-flag gates
- Admin audit log view

## Phase 10 — Public Site & CMS-driven Pages  ☐
- CMS-backed landing (hero, features, testimonials, pricing)
- `/{slug}` pages: about, blog, careers, press, privacy, terms, security,
  changelog, roadmap, support
- Contact + demo-booking forms (rate-limited, spam-trapped) → admin inbox

## Phase 11 — Mobile JWT API  ☐
- `/api/mobile/*` mirroring web capability: auth, dashboard, land, crops,
  activities, finance, inventory, employees, livestock, documents, reports,
  compliance, weather, market, notifications, funder dashboard
- Vendored JWT (access + refresh), identical farm-scoping, CORS allowlist
- `sync` batched offline-queue processor (activities / finance / documents)

## Phase 12 — Hardening & Launch  ☐
- Full OWASP Top-10 review pass with evidence per item
- Manual pen-test checklist (authz matrix, IDOR sweep, CSRF, upload abuse,
  rate-limit verification, header audit, error-leak audit)
- Performance: query/index review, N+1 sweep, asset caching, gzip
- Backup strategy (off-box, encrypted) + restore drill
- `DEPLOY.md` finalised; go-live checklist

---

## Cross-cutting (every phase)
- `declare(strict_types=1)`, PSR-12 style
- Repository methods farm-scoped; no id from request body trusted
- New user input → `Validator`; new output → `e()`
- Each phase ends with: tests green + a short **security-review note** stating
  which vulnerability classes were checked for the code in that phase
