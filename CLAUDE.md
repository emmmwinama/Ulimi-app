# Working in this repo

## Keep two reference files current

Whenever a change adds, removes, or changes access to a feature, or changes
the database structure, update the matching reference file **in the same
change** — not as separate follow-up work:

- **`docs/FEATURES.md`** — every route/controller-action/permission in the
  app, organized by feature area. Touched `app/routes.php` or a permission
  check? Update the corresponding row (or add a new section, following the
  existing pattern) here too.
- **`database/schema.sql`** — the consolidated, generated schema snapshot.
  Never hand-edit it. Add a new numbered file to `database/schema/` (next
  sequence number), then:
  ```
  php database/migrate.php --seed
  php database/dump_schema.php
  ```
  Commit the new migration file and the regenerated `schema.sql` together
  with the code change.

Both files exist so a reader (human or a future session) can find "what does
this app do and how do I reach it" and "what does the database actually look
like" without reconstructing them from the code by hand.
