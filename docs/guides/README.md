# Ulimi — User Guides

Farmer-facing "how do I…" guides, one per feature area, written in plain
language for the person using the app — not for developers (that's what
`ARCHITECTURE.md`/`ROADMAP.md` are for). Added as each feature ships, so
this list grows alongside `ROADMAP.md`'s phases rather than being written
all at once at the end.

These are source content for now — not yet wired into the in-app CMS pages
(`cms_pages` / the `/{slug}` public routes) or a farmer-facing in-app help
panel. Publishing them into the app is a separate, later task; write them
here first so the words exist before deciding where they're surfaced.

| Guide | Covers |
|---|---|
| [Reminders & Alerts](reminders-and-alerts.md) | Notifications bell, harvest/activity/low-stock/price/livestock/weather alerts |
| [Pest & Disease Log](pest-and-disease-log.md) | Reporting crop incidents, photo/voice attachments, outbreak alerts |
| [Inventory & Equipment](inventory-and-equipment.md) | Batch/expiry/supplier tracking, low-stock and expiry alerts, the Equipment section and maintenance logs |
| [Post-Harvest & Storage](post-harvest-and-storage.md) | Logging storage/drying/loss details per harvest, collection & transport on a sale |

Style notes for future guides:
- Address the farmer directly ("you"), short sentences, no jargon
  (say "notifications" not "lazily-generated dedupe records")
- Structure: what it does → where to find it → how to turn it on/off or
  configure it → what to do when you get one
- One guide per roadmap phase's user-facing surface, named after the
  feature, not the phase number
