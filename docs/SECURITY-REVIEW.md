# Ulimi/AgriVault — Security Review (Phase 12)

Dated 2026-09-11, against `phase-build` at the end of Phase 11. This is a
self-review against the OWASP Top 10 (2021) categories plus the project's own
threat model (multi-tenant farm data, a mobile JWT API, free shared hosting).
Each item states what was checked, how, and the evidence. Confidence levels
are stated honestly — this replaces neither a third-party penetration test
nor testing against the real biz.na.ht environment before real farmer data
goes live there (see the platform caveat in `ARCHITECTURE.md` §6).

## A01:2021 — Broken Access Control

**Control:** every farm-scoped repository method takes `farmId` from
`FarmContext::current()` (resolved server-side from the session/JWT +
re-verified membership), never from request input; every `find()`/`update()`/
`delete()` includes `WHERE farm_id = :fid` alongside the row id.

**Verified (IDOR sweep):** logged in as one farm's owner, attempted to load a
second farm's field (edit), crop planting (show), activity (show), finance
transaction (edit), employee (edit), livestock animal (show), and inventory
item (sell) — all seven by guessing/copying the other farm's real row id.
Every attempt returned a 302 "not found" redirect with **zero data from the
other farm** in the response body (grepped for the other farm's distinctive
location/name strings — no matches). Confirmed via `Middleware\Can` (deny by
default, `Authz::can()` per role) on every write route, and `Middleware\
SubscriptionLimit` blocking creates past a tier's cap (tested: lowered a
tier's `max_fields` to 1, got the expected 403 with a clear message, restored
it, creation succeeded again).

**Verified (mobile API):** authenticated as one user, sent `X-Farm-Id` set to
a farm that user does **not** belong to. `ResolveFarmContextApi` correctly
ignored the spoofed header and fell back to the user's real farm — response
contained only that user's own data.

**Admin/customer separation:** `Auth` and `AdminAuth` are separate classes
with separate session keys (`auth` vs `admin`); an admin route requires
`AuthenticateAdmin`, a customer route requires `Authenticate` — neither
credential substitutes for the other.

## A02:2021 — Cryptographic Failures

- Passwords: Argon2id when the PHP build supports it, else bcrypt cost 12
  (`Core\Auth::hash()`), same for `AdminAuth`.
- Activation/reset/invite tokens: random 32-byte tokens, **only a keyed
  HMAC-SHA256 hash is stored** (`Support\Token`), so a DB leak alone can't
  produce a usable token. Verified via `hash_equals` (constant-time).
- Mobile refresh tokens: same hashed-storage pattern (`api_refresh_tokens`).
- Mobile access tokens: `Core\Jwt`, HS256, hard-codes the algorithm (never
  trusts an `alg` claim from the token) — closes the classic `alg:none` /
  algorithm-confusion JWT vulnerability class by construction.
- Secrets (`app.key`, DB credentials, SMTP credentials) live only in
  `config/config.local.php`, git-ignored, never logged (`Logger` redacts
  keys named password/token/secret/key/authorization/cookie).
- Session cookie: `HttpOnly`, `SameSite=Lax`, `Secure` when the request is
  HTTPS; session payloads live in a DB table, not the shared filesystem
  (relevant on shared hosting where `/tmp` is not private).

## A03:2021 — Injection

- **SQL:** every query goes through `Core\Database` with named placeholders;
  `PDO::ATTR_EMULATE_PREPARES = false` (native prepares). Swept the codebase
  for string-interpolated SQL (`grep` for `"SELECT.*\$` etc. across `app/`):
  every dynamic identifier (table/column name) either comes from a fixed
  developer-written map with an `in_array` allowlist check (e.g.
  `LivestockRepository`'s event tables, `SubscriptionLimit`'s resource map),
  or is passed through `Database::quoteIdent()`, which regex-validates the
  identifier and throws on anything unexpected. No request-controlled value
  ever reaches a table/column position. **Two known repeated-placeholder
  bugs were caught during Phase 3/7 testing** (native prepares reject a
  named parameter bound more than once — `RateLimiter::hit()` and
  `CreditScore::compute()`'s activity-cost subquery) and fixed by splitting
  into distinct placeholder names; both are now covered by the smoke tests
  that originally caught them.
- **XSS:** `View::e()` HTML-escapes by default; templates call it explicitly
  per value (a deliberate, diff-reviewable convention rather than
  auto-escaping magic). Swept `app/Views` for `<?= $var ?>` patterns not
  wrapped in `e()` — every match found was either an `int`/`count()` (can't
  carry a payload) or a boolean/ternary producing a fixed literal
  (`'selected' : ''`), never raw user text. The two deliberate raw-output
  points are `partials/icon.php` (static, developer-authored SVG path data)
  and `pages/generic.php`'s CMS page body (admin-authored HTML, same trust
  model as any CMS — never user/farmer input).
- **CSRF:** `Middleware\VerifyCsrf` on every non-GET route; token in a hidden
  form field, per-session, `hash_equals` comparison, rotated on login/logout/
  password change. Swept every `$router->group()`/route registration in
  `routes.php`: every state-changing route traces back to `$web` (which
  includes `VerifyCsrf`) except the mobile API (correctly stateless
  Bearer-JWT auth, not cookie-based, so CSRF doesn't apply there — the
  standard exemption for token APIs) and the read-only `/health` and
  `/{slug}` routes.

## A04:2021 — Insecure Design

- Multi-tenancy is enforced structurally (farm_id required in every
  operational table + every repository signature), not bolted on per-query.
- Subscription tier limits (`Middleware\SubscriptionLimit`) and role
  permissions (`Authz`, deny-by-default) are enforced server-side on every
  write route, not just hidden in the UI.
- File uploads: extension **and** declared MIME **and** `finfo`-sniffed
  magic bytes must all agree with a 4-type allowlist (pdf/jpg/png/webp);
  size-capped; stored under a random 32-hex name **outside the web root**,
  served only through an authenticated proxy. A renamed `.php` masquerading
  as a PDF is rejected because its actual bytes don't match.
- Lazy (cron-free) subscription expiry, notification generation and mail
  delivery are a deliberate, documented design trade-off for the no-cron
  host, not an oversight (see `ARCHITECTURE.md` §6, §8).

## A05:2021 — Security Misconfiguration

- `Middleware\SecurityHeaders` sets CSP, `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, HSTS in
  production, and `X-Robots-Tag: noindex` on every app/admin/API path — on
  **every** response, verified via `curl -D-` during Phase 1-6 testing.
- CSP is `default-src 'self'` with two narrow, necessary exceptions:
  `style-src 'unsafe-inline'` (the templates use inline `style=""`
  attributes throughout — accepted trade-off, documented in
  `ARCHITECTURE.md`; XSS is still blocked by `script-src 'self'` and output
  escaping, so this only affects style injection, a materially lower-risk
  class) and `img-src` allowing the three raster map-tile hosts (OSM, Esri,
  Carto) — images only, no scripts.
- `display_errors` is forced off and a generic 500 page is shown whenever
  `app.debug` is false (`ErrorHandler`); stack traces are logged to
  `storage/logs`, never sent to the client, in that mode. `config.php`
  defaults `app.debug` to `false` — a fresh deploy is safe by default even
  before `config.local.php` is filled in.
- `storage/`, `config/`, `database/`, `vendor/` are all outside the
  recommended web root (`public/`); the root `.htaccess` denies them
  explicitly for hosts that can't move DocumentRoot. `X-Powered-By` is
  suppressed.

## A06:2021 — Vulnerable and Outdated Components

Two vendored third-party components, both pinned and hand-placed (no
Composer on the host):

| Component | Version | Source |
|---|---|---|
| Leaflet + leaflet-draw | 1.9.4 / 1.0.4 | cdnjs, self-hosted under `public/assets/vendor/leaflet` |
| PHPMailer | 6.9.1 | official GitHub release tag, self-hosted under `vendor/phpmailer` |

No other runtime dependencies. Everything else (routing, ORM-free DB layer,
auth, sessions, CSRF, JWT, validation) is first-party code in this repo,
reviewed in full rather than pulled in as an opaque dependency.

## A07:2021 — Identification and Authentication Failures

- Rate limiting (`Core\RateLimiter`, DB-backed fixed window) on: web login,
  admin login, API login, API refresh, register, activate, forgot/reset
  password, account password change, team-invite accept, contact form, demo
  booking. Verified the web login limiter trips after repeated bad attempts
  during testing (hit it accidentally, confirmed the 429/`Too many attempts`
  response, cleared and continued).
- Login responses are enumeration-safe: identical error message and a
  dummy `password_verify()` call on an unknown email, so timing doesn't
  reveal whether an account exists. Same pattern for password reset.
- Session fixation: `session_regenerate_id(true)` on every login/logout;
  CSRF token rotated alongside it.
- Refresh token rotation (mobile API): a refresh token is single-use —
  verified that reusing an already-exchanged refresh token returns 401.
- Password change / password reset revoke every other live session for
  that account (`DELETE FROM sessions WHERE user_id = ...`).

## A08:2021 — Software and Data Integrity Failures

- The `live.sqlite` importer runs inside one DB transaction (all-or-nothing)
  and is idempotent-guarded (`--force` required to re-run against an
  existing account). Verified zero true mojibake bytes and zero orphaned
  foreign keys after import (byte-level check, not just a display check).
- No unsigned/unverified data is deserialized from an external source
  (the mobile API only accepts validated, typed JSON fields — never
  `unserialize()` anywhere in the codebase).

## A09:2021 — Security Logging and Monitoring Failures

- `Core\AuditLog` records security- and money-relevant events (logins,
  failed logins, activation, password changes/resets, team role changes,
  subscription/tier/payment changes, admin actions, document uploads/
  downloads, API logins/syncs) with actor, IP, and a redacted meta payload.
  Visible in the admin dashboard's recent-activity feed.
- `Core\Logger` writes application errors to `storage/logs`, redacting any
  key that looks like a credential, and is the only sink for exception
  detail in production (never echoed to the client).

## A10:2021 — Server-Side Request Forgery

- The only outbound HTTP calls the app makes are to a fixed, hardcoded host
  (`api.open-meteo.com`, `Services\Weather`) with `CURLOPT_PROTOCOLS =
  CURLPROTO_HTTPS` and no user-controlled URL component beyond a rounded
  lat/lng pair (bounded numeric input, validated as `numeric` before use,
  not a URL or hostname). No feature accepts an arbitrary URL to fetch.

---

## Known gaps / accepted risk (stated plainly)

- **Hosting platform, not this app's code:** biz.na.ht is free shared
  hosting — co-tenants on the box, no control over TLS termination, DB
  credentials in a file near the web root. Acceptable for a pilot with one
  real farm; not for onboarding paying farmers with credit/payment data at
  scale without moving to an isolated host. (Restated from
  `ARCHITECTURE.md` §6 — the single most important caveat in this review.)
- No third-party penetration test has been performed; the above is a
  self-review plus targeted adversarial testing (IDOR sweep, header audit,
  rate-limit trip, refresh-token replay) by the same author as the code.
- `style-src 'unsafe-inline'` in the CSP is a deliberate, bounded trade-off
  (see A05) rather than the tightest possible policy.
- Mobile API surface is intentionally a subset (Phase 11) — see
  `ROADMAP.md` for what's deferred and why.
- This review has not been re-run against the actual biz.na.ht environment
  (PHP version, available extensions, TLS behaviour may differ from the
  local WAMP/MariaDB dev setup this was built and tested against).
