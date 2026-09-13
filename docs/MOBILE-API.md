# Ulimi — Mobile API Integration Guide

Reference for building the native mobile client against Ulimi's backend. This is the
API a mobile app talks to — for the overall system design see `ARCHITECTURE.md`; for
farmer-facing feature explanations see `guides/`.

All mobile endpoints live under `/api/mobile` and are implemented in
`app/Controllers/Api/*`, wired in `app/routes.php`. They are a parallel surface to the
session-based web app (`app/Controllers/Farm/*`) — same repositories and business
rules underneath, different auth/transport (JWT + JSON instead of cookies + HTML).

## 1. Base URL & conventions

- Base path: `https://<host>/api/mobile`
- All requests/responses are JSON (`Content-Type: application/json`), **except**
  file-upload endpoints, which are `multipart/form-data`.
- Success shape: `{"data": ...}` — an object, array, or (for a handful of
  destroy/mark-read actions) `{"data": true}`.
- Validation error: HTTP 422, `{"errors": {"field_name": ["message", ...], ...}}`.
- Permission/auth error: HTTP 401/403, `{"error": "message"}`.
- Not found: HTTP 404, `{"error": "message"}`.
- Rate-limited: HTTP 429; batch-too-large: HTTP 413. Both `{"error": "message"}`.
- No pagination yet — index endpoints return the full farm-scoped list. Fine at
  current data volumes; revisit if a farm's activity/transaction history grows large.

## 2. Auth flow

Implemented in `App\Controllers\Api\ApiAuthController` (`app/Core/Jwt.php` for token
signing, `App\Repositories\ApiTokenRepository` for refresh-token storage).

```
POST /api/mobile/login        { email, password, device? }  →  token pair
POST /api/mobile/refresh      { refresh_token }              →  new token pair
POST /api/mobile/logout       { refresh_token? }             →  { ok: true }
```

**Token pair response:**
```json
{
  "access_token": "eyJ...",
  "refresh_token": "opaque-random-string",
  "expires_in": 7200,
  "user": { "id": "...", "name": "...", "email": "..." }
}
```

- **Access token**: signed JWT (HS256, `app/Core/Jwt.php`), 2-hour TTL. Send as
  `Authorization: Bearer <access_token>` on every request after login.
- **Refresh token**: opaque string, stored hashed server-side, 30-day TTL,
  **single-use** — every `/refresh` call rotates it (the old one is revoked, a new
  one is returned). Store the *latest* refresh token only; using a stale one after
  rotation returns 401.
- On 401 from any endpoint (access token expired), call `/refresh` with the stored
  refresh token and retry the original request once with the new access token. If
  `/refresh` itself 401s, the refresh token is dead — force a full re-login.
- `/login` is rate-limited per IP (8 attempts / 15 min) and per-account server-side;
  handle 429 with backoff, not an immediate retry.
- The user is re-verified from the database on **every** request (not trusted from
  JWT claims alone), so a deactivated account is rejected immediately even with an
  unexpired access token — don't cache "is this user active" client-side.

## 3. Farm context

A user can belong to more than one farm. Every authenticated request (except
login/refresh/logout) is scoped to one active farm, resolved by
`App\Middleware\ResolveFarmContextApi`:

- Send `X-Farm-Id: <farm_id>` to pick which farm the request applies to. It's
  re-verified against the user's membership on **every** request — you can't spoof
  access to a farm you're not a member of by guessing an id (403 if you try).
- If you omit the header (or send a farm id that isn't yours), the server falls back
  to the user's first farm. For a single-farm user this means the header is
  optional; for a multi-farm user (or a cooperative member with farms under
  different owners) **always send it explicitly** — don't rely on the fallback.
- `GET /api/mobile/farm-context` returns the active farm, the user's role on it, and
  the list of all farms the user can switch between — call this after login to
  populate a farm switcher and learn the id to send as `X-Farm-Id` going forward.

## 4. CORS

`App\Middleware\CorsMobile` only reflects `Origin` for `http://localhost:8081` /
`http://127.0.0.1:8081` (the Expo dev server default). A packaged mobile app sends no
`Origin` header at all, so this allowlist doesn't affect production API calls from the
built app — it only matters for a browser-based dev/debug client. If you add a web-based
debug tool on a different port, extend the allowlist in that middleware.

## 5. File uploads

Used by: `POST /documents` (file is the primary resource, required), and as an
optional attachment on `POST /incidents` (`attachment`), `POST /climate-events`
(`evidence`), and the `health` kind of `POST /livestock/animals/{id}/events/{kind}`
(`attachment`).

- `multipart/form-data`, the file field alongside the endpoint's normal text fields.
- Allowed types (checked by real magic-byte sniffing, not just the extension or
  declared MIME — `App\Services\Upload`): PDF, JPG, PNG, WEBP, MP3, M4A, OGG.
- Max size: `Config::get('uploads.max_bytes')`, currently 8 MB.
- A rejected file returns 422 with the error under the field name you used
  (`document`, `attachment`, or `evidence`).
- On the optional-attachment endpoints, an upload failure does **not** fail the whole
  request — the parent record (incident/climate event/health record) still saves;
  check the response for whether `media`/attachment data is present to know if the
  file actually attached.
- Download: `GET /documents/{id}/download` streams the file directly (not a JSON
  response) — the file's real name/type is on the record from `GET /documents`.

## 6. Server-forced and immutable fields

Don't send these as writable — they're ignored or overridden server-side, matching
the web app's own invariants exactly:

- **Finance transactions**: `source` is always forced to `"manual"` on create/update.
  Transactions with `source: "inventory_sale"` (auto-created by an inventory sale)
  are **not editable** via `PUT /finance/{id}` — you'll get a 422.
- **Crop incidents**: `status` is forced to `"open"` on create; only settable via
  `PUT /incidents/{id}` once the incident exists.
- **Immutable after create** — accepted on the update payload but ignored, not
  errored: `field_id` on crop plantings (`PUT /crops/{id}`), `livestock_type_id` on
  animals (`PUT /livestock/animals/{id}`).
- **Silently nulled, not rejected, if the id doesn't belong to your farm**:
  `field_id`/`crop_field_id` on transactions, `affected_crop_field_id` on climate
  events, `buyer_id` on offers/sales, `parent_id` on animals. If you see a `null`
  come back for one of these after sending a value, the id you sent wasn't valid for
  this farm — it's not a silent failure of the whole request.

## 7. Endpoint reference

Every group below sits under `/api/mobile`, behind `AuthenticateApi` +
`ResolveFarmContextApi`. "Permission" is the `Authz` capability the current farm
member's role needs; a mismatch returns 403.

| Group | Endpoints | Permission | Notes |
|---|---|---|---|
| Dashboard | `GET /dashboard` | — | Summary tiles, same data as the web dashboard. |
| Fields | `GET,POST /fields`, `PUT,DELETE /fields/{id}` | `fields.manage` for writes | Delete blocked (422) if the field has crop plantings. |
| Crops | `GET,POST /crops`, `GET,PUT /crops/{id}`, `POST /crops/{id}/archive\|restore` | `crops.manage` | `crop_type` is a free-text name, resolved-or-created into a type — not a foreign key you look up first. |
| Incidents | `GET,POST /incidents`, `GET,PUT,DELETE /incidents/{id}` | `crops.manage` | Optional `attachment` upload. See §6 for `status`. |
| Activities | `GET,POST /activities`, `PUT,DELETE /activities/{id}` | `activities.manage` | Mobile captures core fields only (type/date/field/notes) — labour/input/other-cost line items are web-only. |
| Livestock | `GET,POST /livestock/types`, `DELETE /livestock/types/{id}`; `GET,POST /livestock/animals`, `GET,PUT,DELETE /livestock/animals/{id}`; `GET,POST /livestock/animals/{id}/events/{kind}` (`kind` ∈ health,production,weight,expense), `POST .../events/{kind}/{eventId}/delete`; `POST /livestock/animals/{id}/sell` | `livestock.manage` | `weight` events also update the animal's current-weight field as a side effect. `sell` sets status to `Sold` and books an Income transaction. |
| Inventory | `GET,POST /inventory`, `GET,PUT,DELETE /inventory/{id}`, `POST /inventory/{id}/sell` | `inventory.manage` | `sell` checks stock sufficiency, books an Income transaction, and records the sale — all in one server-side DB transaction. |
| Equipment | `GET,POST /equipment`, `GET,PUT,DELETE /equipment/{id}`, `GET,POST /equipment/{id}/logs`, `POST .../logs/{logId}/delete` | `equipment.manage` | |
| Yields | `GET,POST /yields`, `GET,PUT,DELETE /yields/{id}`, `GET,POST /yields/{id}/storage` | `yields.manage` | `storage` is an upsert (one storage record per harvest). |
| Documents | `GET,POST /documents`, `GET /documents/{id}/download`, `POST /documents/{id}/delete` | `documents.manage` | See §5. |
| Employees | `GET,POST /employees`, `GET,PUT,DELETE /employees/{id}` | `employees.manage` | Delete soft-deactivates (`is_active=0`) instead of hard-deleting if the employee has labour records attached. |
| Team | `GET /team`, `POST /team/invite`, `POST /team/{memberId}/role`, `POST /team/{memberId}/remove` | `team.manage` (`team.view` for index) | No `accept` endpoint — accepting an invite is a one-time emailed-link flow, handled on the web, not a mobile screen. |
| Climate events | `GET,POST /climate-events`, `GET,PUT,DELETE /climate-events/{id}` | **`finance.manage`/`.view`** | Deliberately piggybacks on the finance permission, not a dedicated one — matches the web app. Optional `evidence` upload. |
| Cooperatives | `GET,POST /cooperatives`, `POST /cooperatives/join`, `GET /cooperatives/{id}`, `POST /cooperatives/{id}/members/{farmId}/remove`, `POST /cooperatives/{id}/contributions`, `POST /cooperatives/{id}/sales` | Role-based in-controller (chair/secretary/treasurer via `CooperativeContext::canManage()`), not a declared permission string | The `{id}`-scoped routes run `ResolveCooperativeContext` first — 404 if your active farm isn't a member of that cooperative. |
| Market | `GET /market/prices`, `GET,POST /market/buyers`, `POST /market/buyers/{id}/delete`, `GET,POST /market/offers`, `POST /market/offers/{id}/status`, `POST /market/offers/{id}/delete` | **`crops.manage`/`.view`** | Also piggybacks on an existing permission, matching web. `prices` is read-only reference data (not farm-scoped — admin-managed). |
| Notifications | `GET /notifications`, `GET /notifications/unread-count`, `POST /notifications/{id}/read`, `POST /notifications/read-all` | none (any farm member) | Read/mark-read only — notifications are system-generated, no create endpoint. |
| Finance | `GET,POST /finance`, `PUT,DELETE /finance/{id}` | `finance.manage` | See §6 for `source`. |
| Sync | `POST /sync` | per-resource (see §8) | Offline batch queue. |

`SubscriptionLimit` is wired on exactly five create routes — `POST /fields`,
`POST /crops`, `POST /activities`, `POST /finance`, `POST /employees` — because those
are the only resources with a plan-limit column today. A 403 from one of these means
the farm's subscription tier has hit its limit for that resource. Every other
resource (livestock, inventory, equipment, yields, documents, climate events,
cooperatives, market, incidents) has **no plan-limit enforcement yet** — this matches
the web app's own current behavior, not a mobile-specific gap.

## 8. Offline sync batch (`POST /sync`)

For a mobile app's local-first queue: the device can create records while offline,
then flush them in one call once it has connectivity. **Create-only** — there is no
update/delete via this endpoint. Editing or deleting an existing record always goes
through the regular per-resource `PUT`/`POST .../delete` endpoint above, once online.

Request: an object with any of these array keys (all optional — send only the ones
you have queued items for), each item tagged with a client-generated `client_id`:

```json
{
  "activities": [{ "client_id": "local-1", "field_id": "...", "activity_type": "Weeding", "date": "2026-09-11" }],
  "transactions": [...],
  "crop_incidents": [...],
  "livestock_health": [...],
  "livestock_weight": [...],
  "livestock_production": [...],
  "livestock_expenses": [...],
  "inventory_sales": [...],
  "equipment_logs": [...],
  "harvest_yields": [...]
}
```

Each array's item shape matches that resource's normal `POST` body (see §7's linked
endpoint), plus `client_id`. `livestock_*` items additionally need an `animal_id`.
`inventory_sales` items need `inventory_item_id`; `equipment_logs` need
`equipment_id`; `harvest_yields`/`crop_incidents` need `crop_field_id`.

At most 50 items total across all arrays combined per call (413 if over).

Response: same keys back, each item replaced with a per-item result — **one bad item
never fails the batch**:

```json
{
  "results": {
    "activities": [{ "client_id": "local-1", "ok": true, "id": "01M2..." }],
    "crop_incidents": [
      { "client_id": "local-2", "ok": true, "id": "01M2..." },
      { "client_id": "local-3", "ok": false, "errors": { "crop_field_id": ["Unknown crop planting."] } }
    ],
    ...
  }
}
```

Match responses back to your local queue by `client_id`; only clear/retry the items
that came back `ok: false`, after fixing whatever the `errors` object flags.

**Deliberately excluded from the batch queue** (create these via their own endpoint,
online, not queued offline): fields, crop plantings, employees, team invites,
cooperative actions, market buyers/offers, and documents. Documents specifically
can't go through a JSON batch at all since the file bytes need a multipart request —
queue the metadata locally if you like, but upload it directly once online.

A resource only appears in the batch if the current farm member has that resource's
manage permission — same permission-to-resource mapping as §7's table.

## 9. Known gaps / not yet built

- No mobile client exists yet — this API was built ahead of the app to have
  something for it to integrate against.
- Push notifications: not implemented. `GET /notifications` is poll-based only.
- No pagination on any index endpoint (see §1) — fine for now, revisit if needed.
- `SubscriptionLimit` gaps noted in §7 are pre-existing on the web app too, not
  something mobile introduced.

## 10. Local dev gotcha (if you're testing against a local Apache install)

If Bearer auth mysteriously returns 401 with a token that decodes fine, check that
Apache is actually forwarding the `Authorization` header to PHP — it doesn't by
default under `mod_rewrite`. Fixed in `public/.htaccess`:

```apache
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [E=HTTP_AUTHORIZATION:%1]
```

If you stand up a fresh environment without this (or with a different web server —
nginx needs its own equivalent `fastcgi_param HTTP_AUTHORIZATION`), Bearer auth will
silently fail with a generic `{"error":"Unauthenticated."}` and no other clue.
