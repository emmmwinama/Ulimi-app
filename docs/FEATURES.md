# Ulimi (AgriVault) — Feature & Route Reference

Every user-reachable function in the app, and the URL that reaches it. Source
of truth is `app/routes.php` — this file is a readable index over it, not a
replacement; if the two ever disagree, `routes.php` wins and this file is stale.

**Maintenance rule**: whenever a route, controller action, or permission
changes — new feature, renamed endpoint, changed access level — update the
relevant row(s) here in the same change. Same discipline as code comments:
if you touched `app/routes.php`, touch this file too. See also the schema
maintenance rule at the bottom.

## How to read this

- **Path** is relative to the app's base URL (e.g. `/crops` → `https://<host>/crops`).
- **Access** names the permission a farm member's role needs (`Can:<resource>.<view|manage>`,
  checked by `App\Middleware\Can` against `App\Core\Authz`). "Any member" means
  no permission check beyond being logged in with an active farm. Roles are
  `owner`, `manager`, `agronomist`, `accountant`, `field_worker`, `viewer` —
  see `docs/ARCHITECTURE.md` §7 for the full role→permission matrix.
- Routes with no `Can:` guard but under a resource marked "(piggybacks on X)"
  deliberately reuse another resource's permission — not a bug, see the inline
  comment in `routes.php` at that spot.
- The mobile API (`/api/mobile/...`, JWT auth) mirrors most of this with a
  parallel route table — full reference in **[MOBILE-API.md](MOBILE-API.md)**,
  not repeated here.

---

## Public (no login)

| Path | What it does |
|---|---|
| `/` | Marketing homepage |
| `/{slug}` | CMS-managed public pages (pricing, about, etc. — admin-editable via `/admin/cms`) |
| `/contact`, `/demo` | Contact form / demo-request form submission |
| `/shared/reports/{token}` | Token-gated read-only view of a farm's shared record pack (no login — see `docs/ARCHITECTURE.md` §7 "Report share links") |
| `/health` | Liveness check (used by hosting/uptime monitoring, not a user page) |

## Auth

| Path | What it does |
|---|---|
| `/login`, `/register` | Sign in / create an account |
| `/activate` | Consume an email activation link |
| `/forgot-password`, `/reset-password` | Password reset flow |
| `/logout` | End session |
| `/invite` (GET while logged out, POST to accept) | Accept a team invite email link — creates/links the account |
| `/onboarding/farm` | First-farm setup wizard, shown right after registration |

## Dashboard & farm switching

| Path | Access | What it does |
|---|---|---|
| `/dashboard` | any member | Home screen: getting-started checklist, key stats, season profitability, crop mix, quick actions |
| `/farm/switch` (POST) | any member | Switch the session's active farm (multi-farm users/cooperative members) |

## Fields & farm map

| Path | Access | What it does |
|---|---|---|
| `/fields` | `fields.view` | List fields; create/edit/delete via `fields.manage` (create is subscription-limited) |
| `/map` | `fields.view` | Whole-farm map — field boundaries + custom markers |
| `/fields/{id}/map` | `fields.view` | Single-field map — draw/edit boundary, add zones |

## Crops, calendar, seasonal templates, incidents

| Path | Access | What it does |
|---|---|---|
| `/crops` | `crops.view` | Crop plantings list; create/edit via `crops.manage` (create is subscription-limited); archive/restore a planting |
| `/crops/{id}` | `crops.view` | Planting detail — indicative `CropTimeline` stage schedule for that crop |
| `/calendar` | `crops.view` | Month-grid view of plantings, expected harvests, `CropTimeline` stage due-dates, and logged activities (`App\Services\CalendarService`) |
| `/templates` | any member | Browse curated seasonal workflows (activity schedule + payroll roles + sales-evidence checklist) per crop — read-only reference content, `App\Services\SeasonalTemplates` |
| `/incidents` | `crops.view` | Pest & Disease log; report/edit via `crops.manage`; optional photo/voice attachment |

## Activities & employees

| Path | Access | What it does |
|---|---|---|
| `/activities` | `activities.view` | Farm activity log (type, date, field, labour/input/other cost lines); create is subscription-limited |
| `/employees` | `employees.view` | Worker roster + pay rates; create is subscription-limited; delete soft-deactivates if the employee has labour records |

## Yields, post-harvest, inventory, equipment

| Path | Access | What it does |
|---|---|---|
| `/yields` | `yields.view` | Harvest records per planting; `/yields/{id}/storage` records drying/storage/loss detail for one harvest |
| `/inventory` | `inventory.view` | Stock items (harvested produce, seed, fertiliser, etc.); `/inventory/{id}/sell` records a sale (decrements stock, books income, `/receipt` prints it) |
| `/equipment` | `equipment.view` | Equipment register; `/equipment/{id}/logs` records maintenance/usage entries |

## Livestock

| Path | Access | What it does |
|---|---|---|
| `/livestock` | `livestock.view` | Animal types + roster; `livestock.manage` for everything below |
| `/livestock/animals/{id}` | `livestock.view` | One animal's record: health/production/weight/expense event log, parentage |
| `/livestock/animals/{id}/events/{kind}` (POST) | `livestock.manage` | Add a health/production/weight/expense event (`kind` ∈ those four); health events accept an optional attachment; weight events also update the animal's current weight |
| `/livestock/animals/{id}/sell` (POST) | `livestock.manage` | Marks the animal `Sold`, books an income transaction |

## Finance

| Path | Access | What it does |
|---|---|---|
| `/finance` | `finance.view` | P&L overview |
| `/finance/transactions` | `finance.view` | Income/expense ledger; create is subscription-limited; transactions with `source=inventory_sale` aren't directly editable (they're generated, not manual) |
| `/finance/overheads` | `finance.view` | Recurring overhead costs, allocated across activities |

## Climate events *(piggybacks on the `finance` permission)*

| Path | Access | What it does |
|---|---|---|
| `/climate-events` | `finance.view`/`.manage` | Drought/flood/wind/frost/hail loss log, optionally linked to a crop planting, optional evidence photo |

## Cooperatives

| Path | Access | What it does |
|---|---|---|
| `/cooperatives` | any member | List/create/join a cooperative (by `join_code`) |
| `/cooperatives/{id}` | member-of-that-cooperative (`ResolveCooperativeContext`, then role-based `canManage()` for mutating actions — chair/secretary/treasurer) | Group view: members, contributions, collective sales + per-member splits, pooled inventory/production rollup across member farms |

## Reports & sharing

| Path | Access | What it does |
|---|---|---|
| `/reports` | `reports.view` | Overview/crops/finance/analytics/yields/overhead/trends/performance/break-even/season-compare tabs; includes the AI-generated insight (`App\Services\Ai`, no-ops without an API key) |
| `/reports/credit-score` | `reports.view` | Farm creditworthiness score (`App\Services\CreditScore`) |
| `/reports/builder` | `reports.view` | Ad-hoc report/CSV export builder |
| `/reports/pack/{type}` | `reports.view` | Pre-curated evidence pack (`loan`, `buyer`, `audit`, `insurance` — see `ReportsController::PACKS`) |
| `/reports/pack/{type}/share` (POST) | `reports.manage` (owner only, by role matrix) | Generates a `/shared/reports/{token}` link — the one deliberately unauthenticated read path in the app |

## Documents

| Path | Access | What it does |
|---|---|---|
| `/documents` | `documents.view` | Farm document library (deeds, certificates, receipts, contracts, photos); upload/delete via `documents.manage`; also the storage target for incident/climate-event/livestock-health attachments (`linked_type`) |

## Weather, market prices, notifications

| Path | Access | What it does |
|---|---|---|
| `/weather` | `fields.view` | Current conditions + 7-day forecast (Open-Meteo, cached 3h — `App\Services\Weather`) |
| `/market` | `crops.view` | Reference crop price board (admin-maintained, see below) |
| `/market/buyers` | `crops.view`/`.manage` | Buyer contact book |
| `/market/offers` (POST only — list is on `/market`) | `crops.manage` | Track a buyer's purchase offer and its status |
| `/notifications` | any member | System-generated alerts (weather, harvest/activity reminders, low stock, price changes, livestock due dates — `App\Services\NotificationGenerator`) |

## Team, settings, account

| Path | Access | What it does |
|---|---|---|
| `/team` | `team.view` | Member roster; invite/change-role/remove via `team.manage` (team-size limit enforced inline, not via the generic subscription-limit middleware, for a friendlier error) |
| `/settings` | any member (edit form itself has no extra gate — mutating actions expect the owner in practice) | Farm profile edit; `/settings/delete` (POST) deletes the farm |
| `/account` | logged in | Personal profile + password change |

---

## Admin back office (`/admin/...`, separate `admin_users` auth domain — `AuthenticateAdmin`, not farm `Authz`)

| Path | What it does |
|---|---|
| `/admin` | Platform overview: users/subscriptions/revenue/tier stats, recent payments & registrations |
| `/admin/users` | User list + detail; activate/deactivate an account |
| `/admin/subscriptions` | Per-user subscription: status, tier, extend, record a manual payment |
| `/admin/payments` | Payment ledger |
| `/admin/tiers` | Subscription tier definitions (price, per-resource limits, feature flags) |
| `/admin/market` | **Crop price management** — manual add/edit/delete, plus the auto-fetch feed: "Check for updates" pulls WFP's open Malawi food-price data (`App\Services\MarketPriceFeed`, lazy daily check, staple crops only — see `docs/MOBILE-API.md` is not the place, this is web-only admin), shows a pending-updates review queue with old→new price diffs, approve/dismiss per row. Cash crops (tobacco, cotton, sunflower, sugarcane) have no live source and stay fully manual. |
| `/admin/cms` | Public-site content: page content blocks, feature list, testimonials |
| `/admin/inquiries` | Contact-form and demo-request submissions, with a status workflow |

---

## Cross-cutting things that aren't a page

- **AI insights** (`App\Services\Ai`) — Groq-backed narrative summaries embedded into Reports. No dedicated route; no-ops silently if `ai.api_key` is unset in `config/config.local.php`.
- **Notifications** (`App\Services\NotificationGenerator`) — lazily derived on view, not push-generated; no cron on the host.
- **Weather / AI / market-price-feed all share the same "no cron" lazy-refresh pattern** — checked and refreshed on page view once a cache/check TTL has elapsed. See `docs/ARCHITECTURE.md` for why (shared hosting constraint).
- **Mobile API** (`/api/mobile/...`) — JWT-based parallel surface for a native app; full reference in `docs/MOBILE-API.md`.

---

## Keeping this file (and the schema) in sync

Two files must be updated alongside any change to what the app does or stores:

1. **This file** — add/edit/remove the row for any route, controller action, or
   permission you touch. If you add a whole new module, give it its own
   section following the pattern above (path table + one-line purpose per
   row + any access-control quirks called out explicitly, the way Climate
   Events' finance-permission piggyback is above).
2. **`database/schema.sql`** — the consolidated schema snapshot. It is
   *generated*, not hand-edited: add your change as a new numbered file in
   `database/schema/` (next sequence number, one migration per logical
   change, matching the existing files' style), apply it, then regenerate
   the snapshot:
   ```
   php database/migrate.php --seed
   php database/dump_schema.php
   ```
   Commit both the new `database/schema/NNN_*.sql` file and the regenerated
   `database/schema.sql` together with the code change. Never hand-edit
   `schema.sql` directly — the next regeneration will overwrite it.
