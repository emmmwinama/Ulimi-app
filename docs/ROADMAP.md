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
- `Services\ReportBuilder` — sections/columns/filters → CSV, plus an HTML
  record-pack view (`layouts/print`) with a brand-compliant cover + footer
  that the browser turns into a PDF via "Print → Save as PDF" — no
  server-side PDF library. _(Corrected in Phase 17: this entry previously
  said "vendored FPDF"; no such vendoring exists or ever did.)_
- Canned reports: cashflow, crop/field profitability, cost per ha/kg, trends
- Record packs (loan / buyer / audit / insurance) with section filters
- `Services\Traceability` — lot IDs, buyer-ready checklist scoring
- `Services\CreditScore` — cashflow / activity-cost / revenue factors, grade
- Compliance checklist (evidence-gap view)

## Phase 8 — Ancillary Services  ☑
- Schema `010_support`: `market_prices`, `weather_cache`, `farm_documents`,
  `notifications`, `farm_credit_scores`, seasonal `templates`
- Weather: Open-Meteo, 3-hour DB cache, farm-location aware
- Market prices + farmer-facing comparison page (fills an original gap)
- Documents: hardened upload + authenticated proxy download + linking
- Notifications: lazy generation (harvest due, no activity, low inventory,
  price alert), inbox, read state
- Seasonal templates (activity / payroll / sales) by crop
- Settings (farm, account, password, danger zone)

## Phase 9 — Admin Back Office  ☑
- Schema `011_cms`: `site_content`, `cms_pages`, `cms_features`, `cms_media`,
  `testimonials`, `contact_submissions`, `demo_bookings`
- Admin: users (+ activation), tiers, subscriptions (+ lazy auto-expiry),
  payments, inquiries, testimonials, market data, CMS pages/features/media
- Enforce `SubscriptionLimit` across every write path + feature-flag gates
- Admin audit log view

## Phase 10 — Public Site & CMS-driven Pages  ☑
- CMS-backed landing (hero, features, testimonials, pricing)
- `/{slug}` pages: about, blog, careers, press, privacy, terms, security,
  changelog, roadmap, support
- Contact + demo-booking forms (rate-limited, spam-trapped) → admin inbox

## Phase 11 — Mobile JWT API  ☑ (scoped)

**Done, deliberately scoped down.** No mobile client exists or will be built
in this engagement, and the original's own docs note its shipped Expo app
only ever called ~4 of the ~25 mobile endpoint groups — the rest of that
surface was unreachable from any real client. Building all 25 groups here
would be effort spent on endpoints nothing calls. Instead: a correct,
fully-verified core that mirrors what the *real* shipped mobile client
actually used (login, dashboard, fields, activity/finance capture, batched
offline sync) — the same shape, extensible the same way, just not padded
with unused surface area.

- `Core\Jwt`: hand-written ~60-line HS256 encode/decode (not vendored — the
  format is simple enough that an unfamiliar vendored file would be less
  auditable than reading it directly). Hard-codes the algorithm so a token
  can never claim `alg:none` or a different algorithm.
- Access tokens: short-lived (2h) signed JWT, `Authorization: Bearer`.
  Refresh tokens: 30-day opaque string, stored only as a keyed hash
  (`api_refresh_tokens`, schema 013_mobile), single-use rotation on
  `/refresh` (reusing a spent one is rejected).
- `Middleware\AuthenticateApi` re-reads the user from the DB every request
  (not just trusted from JWT claims) so a deactivated account is rejected
  immediately even with an unexpired token.
- `Middleware\ResolveFarmContextApi`: active farm from `X-Farm-Id` header
  (re-verified against membership every request), falling back to the
  user's first farm — same Authz/FarmContext/SubscriptionService the web
  side uses, so permissions and read-only-on-lapsed-subscription behave
  identically.
- `Middleware\CorsMobile`: allowlist origins only (dev Expo server), never
  a bare `*`; preflight handled once in the front controller since the
  router only matches explicitly-registered methods.
- Endpoints: login/refresh/logout, farm-context, dashboard, fields
  (list/create), activities (list/create — core fields only; line-item
  costing stays a web feature, matching the real product's mobile/web
  split), finance (list/create), and a batched `/sync` that validates and
  creates each queued item independently (one bad item never fails the
  batch), returning per-`client_id` results so a client retries only what
  failed.
- Reuses the same repositories and Validator as the web app — no
  parallel business logic to drift out of sync.

Verified: CORS preflight 204, login issues a valid JWT + refresh pair,
unauthenticated/bad-token both 401, dashboard reflects real farm data,
field/activity/transaction creation all 201, refresh rotates the token
(old one then 401s on reuse), sync processes a valid+invalid+valid batch
correctly (2 ok, 1 per-item error, nothing lost).

_Deferred:_ inventory/livestock/documents/reports/weather/market/
notifications mobile endpoints, and the funder-dashboard view — add if a
real mobile client is ever built against this API.

## Phase 12 — Hardening & Launch  ☑

`docs/SECURITY-REVIEW.md` — a full OWASP Top-10 pass with evidence per
category, written against this codebase (not a template). Key results:

- **IDOR sweep** (7 resource types — field, crop, activity, transaction,
  employee, livestock animal, inventory item): cross-farm access by guessed/
  copied ID, all 7 blocked with zero data leakage, verified by grepping
  response bodies for the other farm's distinctive strings.
- **Mobile API farm-spoofing**: `X-Farm-Id` header set to a farm the
  authenticated user doesn't belong to — correctly ignored, falls back to
  the user's real farm.
- **SQL injection sweep**: every dynamic table/column name in the codebase
  traced to either a fixed developer-written allowlist or
  `Database::quoteIdent()`'s regex validation; two real bugs caught along
  the way (a named placeholder reused across subqueries, which native
  prepares reject) and fixed — see the review for exactly where.
- **XSS sweep**: every `<?= $var ?>` in `app/Views` not wrapped in `e()`
  checked by hand; all were ints/booleans that can't carry a payload.
- **CSRF coverage**: every state-changing route traced through `routes.php`
  to confirm it inherits `VerifyCsrf`; the only routes that don't are
  read-only or the (correctly stateless) mobile API.
- **Rate-limit verification**: tripped the login limiter for real during
  testing, confirmed the 429 response.
- **Header audit**: full security-header set confirmed present on live
  responses across every phase's testing (CSP, X-Frame-Options, HSTS in
  prod, noindex on app/admin/API).

Also landed this phase:
- **PHPMailer 6.9.1 vendored** (`vendor/phpmailer/`, pinned to the official
  release tag, autoloaded via a second PSR-4-style prefix map in
  `bootstrap.php`) — fulfils the Phase 1 architecture commitment that had
  been running on the `log` driver fallback through Phases 1-11. Verified
  it autoloads and instantiates through the app's own bootstrap.
- `docs/DEPLOY.md` finalised with a restore-drill procedure and a
  15-item go-live checklist (config, HTTPS, email delivery test, admin
  account, seed-data review, `live.sqlite` cleanup, backup-then-restore
  proof, web-root exposure checks, one full end-to-end user pass).
- Performance/index review: every farm-scoped table carries a `farm_id`
  (composite, where queried alongside date/status) index from the schema
  it was introduced in; every list view uses a single JOIN query rather
  than a per-row lookup — spot-checked for N+1 patterns across
  controllers, none found (the one loop that looked suspicious in a grep
  was in-memory counting over an already-fetched result set, not a query).

218 files lint clean, 17 tests green, schema.sql regenerated (50 tables).

_Honest limitation, restated:_ this is a self-review plus targeted
adversarial testing by the same author as the code, not a third-party
penetration test, and it has not been run against the real biz.na.ht
environment. See `SECURITY-REVIEW.md`'s "Known gaps" section.

---

## Phase 13 — Reminders & Proactive Alerts  ☑

Closed a real gap between this document and the code: `NotificationGenerator`
only ever implemented `harvestsDue()` and `noRecentActivity()` — the "low
inventory, price alert" notifications this file claimed under Phase 8 were
never built. Extends the existing lazy-notification pattern; the only schema
change is one nullable column.

- Schema `015_reminders`: `inventory_items.reorder_threshold` (nullable
  `DECIMAL(14,3)`) — surfaced in the stock item form as "Low-stock alert
  below", optional
- `NotificationGenerator` gains four generators, all farm-scoped, all
  going through the existing `NotificationRepository::upsert()` dedupe:
  - `lowStock()` — items at or below `reorder_threshold`, type
    `low_inventory`
  - `priceAlert()` — sales in the last 14 days priced >15% below the
    active `market_prices` average for the same item name, type
    `price_alert`
  - `livestockDue()` — each active animal's *most recent* `animal_health`
    row (correlated subquery, so a superseded record can't re-alert)
    whose `next_due_date` is due/overdue within 7 days, type
    `livestock_due`
  - `weatherAlert()` — non-`info`-severity items from `Weather::advice()`,
    type `weather_alert`
- `Services\Weather::advice()` (static, pure — no I/O) extracts and
  extends the farming-advice thresholds that used to live inline in
  `weather/index.php`: heavy rain, spray conditions, strong wind, high
  heat, frost, dry-spell/irrigation, and a 2-of-3-day flood warning; each
  item carries a `severity` (`info`/`caution`/`warning`) so the
  weather page and the notification generator share one source of truth
  instead of drifting apart. `Weather::sprayConditionOk()` extracted
  alongside it for reuse.
- `run()` now takes the farm row (`FarmContext::current()->farm`) so
  `weatherAlert()` can call `Weather::forFarm()` without a second lookup —
  the one call site (`NotificationsController::index()`) updated.
- Tests: 7 new pure-logic cases for `Weather::advice()` /
  `sprayConditionOk()` in `tests/run.php` (no DB — matches how the rest of
  that runner works); the three DB-backed generators follow the same
  untested-by-`run.php` pattern as the pre-existing `harvestsDue()` /
  `noRecentActivity()`, exercised manually against a scratch DB instead.
- Security-review note: one new input surface (`reorder_threshold` on the
  inventory form) — validated `numeric`/`min:0`/`max` like every other
  numeric field on that form, same farm-scoped write path. The four new
  generator queries are read-only and each carries its own `farm_id` /
  animal→farm join predicate, consistent with the farm-scoping rule
  applied to every other repository query.

## Phase 14 — Pest & Disease Incident Log & Media Attachments  ☑

- Schema `016_incidents` (numbering picked up where the codebase actually
  was — 013/014 were already taken by the mobile API and AI-insights
  phases by the time this landed, ahead of what `ROADMAP.md` had recorded):
  `crop_incidents` (crop_field_id, type, description, severity, status,
  reported_date, treatment_notes, referred_to), farm- and
  crop-field-scoped.
- Media attaches through the existing `farm_documents.linked_to`/
  `linked_type` columns — present since Phase 8, never actually set by any
  caller until now — instead of a parallel photo column. Wired to both
  `crop_incidents` (`CropIncidentsController`) and, while in the
  neighbourhood, `animal_health` (`LivestockController`'s health event
  form gained the same optional attachment, since it was the other
  half of "pest/disease/animal-health" from the original spec and the
  plumbing — `DocumentRepository::forLinked()`/`forLinkedMany()` — was
  already being built). Deleting an incident or a health record cleans up
  its linked documents and stored files instead of leaving orphans.
- `Upload` allowlist gains voice-note formats (`mp3`/`m4a`/`ogg`), same
  extension+MIME+magic-byte agreement check as images/PDF; `Upload::path()`'s
  asset-id shape validation extended to match.
- `Controllers\Farm\CropIncidentsController` — full CRUD (`/incidents`),
  gated on the existing `crops.view`/`crops.manage` permissions rather
  than a new Authz resource key, since incident reporting is a crop-domain
  action and every role that can already manage crop plantings is the
  right set of people to report/manage incidents on them.
- `NotificationGenerator::outbreakAlert()` — 3+ same-`type` incidents on
  one farm within 14 days, using the Phase 13 dedupe/upsert pattern
  (weekly-rotating key so it resurfaces while ongoing). Cross-farm/
  regional outbreak detection is out of scope until Phase 19's
  cooperative layer exists.
- Security-review note: new farm-scoped write surface, same
  `Validator`/CSRF/farm-scoping treatment as every other controller; the
  file-upload hardening is inherited unchanged from Phase 8's `Upload`
  service, just with a wider (still-checked) allowlist.

## Phase 15 — Inventory Hardening  ☑

- Schema `017_inventory2` (`reorder_threshold` had already landed in
  Phase 13): `inventory_items` gains `batch_number`, `expiry_date`,
  `supplier_name`, `supplier_contact`; new `equipment` +
  `equipment_maintenance_logs` tables.
- Equipment is a genuinely separate lifecycle from stock, not just a
  form change: no quantity/depletion, no per-unit sale, but it does need
  a maintenance history that `inventory_items` has no shape for. Existing
  `inventory_items` rows tagged `category = 'equipment'` are left alone
  (that category value stays valid for backward compatibility) — the new
  `Controllers\Farm\EquipmentController` (`/equipment`) is the actively-
  promoted path going forward, not a data migration of old rows.
- `EquipmentController` reuses the `equipment.view`/`equipment.manage`
  Authz resource — already fully defined in `Core\Authz`'s role matrix
  since Phase 1 (owner/manager manage, agronomist/accountant/field_worker
  view-only) but never exercised by any feature until now.
- Inventory list shows a batch-number badge and a colour-coded
  expiry badge (amber inside 30 days, red once past); the add/edit form
  gained batch/expiry/supplier fields.
- `NotificationGenerator::expiringStock()` — in-stock items expiring
  within 30 days or already past expiry, same dedupe/upsert pattern as
  the rest of Phase 13.
- Security-review note: `EquipmentController`/`EquipmentRepository`
  follow the same farm-scoped CRUD + `Validator`/CSRF pattern as every
  other controller; `equipment_maintenance_logs.equipment_id` is
  `ON DELETE CASCADE` so deleting equipment can't orphan its maintenance
  history.

## Phase 16 — Post-Harvest & Loss Tracking  ☑

- Schema `018_postharvest`: `produce_storage` (storage_location,
  drying_method/date, quality_grade, expected_loss_qty, loss_reason),
  one-to-one with `harvest_yields` (`UNIQUE` on `harvest_yield_id`,
  upserted rather than a full CRUD sub-resource — a harvest either has a
  storage record or doesn't). `inventory_sales` gains `collection_point`,
  `transport_method`, `pickup_date`.
- `ProduceStorageRepository::forHarvests()` bulk-loads storage rows for a
  whole yields list (avoids N+1 when badging "Stored" vs "Storage" on
  each harvest row in `yields/index`).
- `YieldsController::storageForm()`/`storeStorage()` (`/yields/{id}/storage`)
  — a single upsert form, not create/edit/delete, matching the 1:1 shape.
- `InventoryController::sell()` / the sell form gained the three
  collection/transport fields, shown under the buyer on the sale-history
  table when set.
- `NotificationGenerator::storageReminder()` — harvests logged in the last
  14 days with no storage record yet, same dedupe pattern as the rest of
  Phase 13.
- Security-review note: same farm-scoped CRUD pattern as every other
  controller; `produce_storage.harvest_yield_id` is `ON DELETE CASCADE`
  so deleting a yield record can't orphan its storage row.

## Phase 17 — Market Access Enhancements  ☑

**Correction to this document's own Phase 7 entry, found while implementing
this phase:** there is no vendored FPDF and no server-side PDF generation
anywhere in the codebase — record packs (`reports/pack.php`) and now the new
sales receipt both use the existing `layouts/print` pattern instead: a
plain, brand-consistent HTML document the browser turns into a PDF via
`window.print()` → "Save as PDF". No PDF library, vendored or otherwise,
was ever added. Treat this as the accurate description; the Phase 7 text
is stale and out of scope to fix retroactively here.

- Schema `019_market2`: `buyers` (farm-scoped contact book), `buyer_offers`
  (optionally linked to a buyer); `transactions` gains `payment_status`
  (unpaid/partial/paid, default `paid` for backward compatibility with
  existing rows); `inventory_sales` gains an optional `buyer_id` FK
  alongside the existing free-text `buyer_name` (kept as a fallback for
  one-off buyers not worth saving).
- `MarketController` grows from a read-only price page into `/market` +
  `/market/buyers` (tabbed): buyer contact book, offer tracking
  (open/accepted/declined/expired) with a per-buyer or ad-hoc offer, all
  gated on the existing `crops.view`/`crops.manage` permissions — same
  reuse-over-new-resource choice as Phase 14's incidents, and consistent
  with the pre-existing `/market` route already doing this.
- Inventory's sell form can attribute a sale to a saved buyer (auto-fills
  the name) or a one-off free-text name; the sale-history table links to
  a `/inventory/{id}/sell/{saleId}/receipt` print-ready receipt.
- Finance transactions gained the `payment_status` field (form + a
  paid/partial/unpaid badge column on the transactions list).
- Security-review note: same farm-scoped CRUD pattern throughout;
  `buyer_offers.buyer_id` and `inventory_sales.buyer_id` are
  `ON DELETE SET NULL` so removing a buyer can't cascade-delete sales or
  offer history, only detach the link.

## Phase 18 — Finance & Insurance Readiness  ☑

- Schema `020_finance2`: `report_share_links` (token hashed at rest via
  `Support\Token`, same primitive as password/invite tokens but
  repeatable-use rather than single-use), `climate_events` (event_type,
  date range, description, estimated_loss_amount, optional
  `affected_crop_field_id`).
- **Consent-based sharing:** `Controllers\Public\SharedReportController`
  (`/shared/reports/{token}`) — the one deliberately unauthenticated read
  path in the app. `ReportsController::createShareLink()`/
  `revokeShareLink()` manage links from the pack page itself, gated on
  `reports.manage` (owner-only by the existing role matrix — deliberately
  the narrowest gate in the app, since this hands data outside the farm).
  A manual per-IP rate limit on *failed* token lookups guards against
  guessing (the generic `Throttle` middleware exempts GETs, so it doesn't
  fit this route); `SecurityHeaders` extended to `noindex`/`no-store` the
  `/shared` prefix so a token URL can't leak via caching or indexing.
  `layouts/print` gained a `public` mode (hides the authenticated "back"
  link, shows a consent notice instead) shared by both the pack view and
  Phase 17's sales receipt.
- **Climate-loss evidence:** `climate_events` + evidence photos via Phase
  14's `farm_documents` linking pattern (`linked_type = 'climate_event'`);
  its own `ReportBuilder` section, added to the `insurance` pack, which
  previously carried no weather/climate data at all. New
  `Controllers\Farm\ClimateEventsController` (`/climate-events`), gated
  on `finance.manage`/`.view`.
- **Formatted P&L:** a `pnl` `ReportBuilder` section (totals grouped by
  type/category via SQL `GROUP BY`, not a PHP aggregation pass) replaces
  the raw `finance` transaction dump in the `loan` pack specifically; the
  raw list stays available in `audit` and the custom report builder,
  where a line-by-line view is more useful.
- **Found and fixed while wiring this up:** `reports/pack.php` computed
  `$farmName`/`$dateRange`/`$purpose` as local variables that never
  actually reached `layouts/print.php` — the view engine's `render()`
  evaluates the layout with the *original* controller-supplied data, not
  a child template's local scope. Every existing record pack has been
  silently showing a blank "Farm:" line since Phase 7. Fixed by having
  both `ReportsController::pack()` and the new `SharedReportController`
  pass these directly; `pack.php` no longer recomputes them.
- **This document's own Phase 7 entry was also wrong:** it claimed a
  "vendored FPDF" that never existed — record packs and the Phase 17
  receipt both already used the browser-print `layouts/print` approach.
  Corrected in place.
- Security-review note: the share-link route is the one new
  unauthenticated surface in this codebase — token entropy (256 bits,
  HMAC'd at rest, constant-time compare via `Support\Token`), expiry,
  revocation, and the manual failed-lookup rate limit all get the same
  scrutiny as the password-reset flow. `ClimateEventsController` and the
  share-link management actions follow the standard farm-scoped
  `Validator`/CSRF pattern.

## Phase 19 — Cooperative & Group Management (MVP)  ☑

Scoped deliberately: member registry + group inventory/production rollup +
collective sales + contribution ledger. Shared-equipment booking calendars
and automated NGO/lender report generation are deferred to a future phase —
this was the largest, most architecturally novel piece of the whole plan
(a second tenant concept alongside farms) and the MVP scoping de-risked it.

- Schema `021_cooperative` (numbering picked up where the codebase
  actually was, same reason as Phases 14/18's numbering notes):
  `cooperatives` (with a short `join_code`, unique), `cooperative_members`
  (cooperative_id, farm_id, role: chair/secretary/treasurer/member,
  mirroring the `farm_members` shape), `cooperative_contributions`
  (a simple ledger, not a payments processor), `cooperative_sales` +
  `cooperative_sale_splits` (per-member quantity/amount on one collective
  sale).
- `Core\CooperativeContext` + `Middleware\ResolveCooperativeContext`,
  modeled directly on `FarmContext`/`ResolveFarmContext` — same
  re-verify-membership-every-request trust boundary, cooperative id taken
  from the route and checked against `cooperative_members` on every
  request, never trusted from a session or request body. Deliberately
  *not* session-based like the farm switcher: a farm can belong to several
  cooperatives at once, so which one is "active" is just whichever URL
  you're on, not a mode you switch into.
- Joining is a shared `join_code` (8 hex chars, regenerated on collision)
  entered by any farm — no async approval step. Simplest thing that lets
  a real group actually form; an invite/approval flow is a reasonable
  follow-up if this needs tightening later.
- `Services\CooperativeStats` — read-only inventory/production rollups
  over member farms' *existing* tables (`inventory_items`,
  `harvest_yields`), scoped to the exact member-farm-id list resolved
  server-side; no data duplication, no new storage for this part.
- `Controllers\Farm\CooperativeController` (`/cooperatives`,
  `/cooperatives/{id}`): create/join, member list + removal, contribution
  ledger, collective sale entry with per-member splits, rollup dashboard.
  Chair/secretary/treasurer-only actions are checked in-controller (flash
  + redirect) rather than via middleware, matching the existing pattern
  in `TeamController` for friendlier UX than a generic 403.
- Security-review note (the cross-cooperative IDOR sweep this phase
  called for): `ResolveCooperativeContext` blocks any farm that isn't a
  member from reaching `show`/`removeMember`/`addContribution`/`addSale`
  for a guessed/copied cooperative id (404, enumeration-safe, same
  treatment as every other farm-scoped resource). `removeMember`'s
  farm-id route param is scoped by `cooperative_id` in the same DELETE,
  so a foreign id is a harmless no-op rather than a cross-tenant write.
  `addContribution`/`addSale`'s split rows explicitly re-verify each
  `farm_id` against `cooperative_members` before writing, rejecting any
  farm not actually in the cooperative.

---

**All seven planned gap-closing phases (13–19) are now done.** The
feature-spec gap analysis from the original planning pass is fully
implemented; see `docs/guides/` for the farmer-facing side of each one.

---

## Cross-cutting (every phase)
- `declare(strict_types=1)`, PSR-12 style
- Repository methods farm-scoped; no id from request body trusted
- New user input → `Validator`; new output → `e()`
- Each phase ends with: tests green + a short **security-review note** stating
  which vulnerability classes were checked for the code in that phase
