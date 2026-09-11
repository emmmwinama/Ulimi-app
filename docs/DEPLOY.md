# Deploying AgriVault to biz.na.ht (or any Composer-less shared host)

> Target: PHP 8.1+, MySQL/MariaDB, Apache + mod_rewrite, no SSH, no Composer,
> no cron. Everything below is done through the host's file manager / SFTP and
> phpMyAdmin, plus one local machine with PHP to run the migrator.

## 1. Database

1. In the host control panel, create a MySQL database and a DB user; note the
   **host, name, user, password** (shared hosts often prefix them, e.g.
   `epiz_123_agrivault`).
2. You will apply the schema in step 4.

## 2. Upload

Upload the whole project tree. Two layouts:

**A — you can set the domain's document root** (preferred): point it at
`public/`. Nothing else is web-reachable.

**B — document root is fixed** (e.g. must be `htdocs/`): upload the project so
that the repo root sits at `htdocs/`. The root `.htaccess` then routes requests
into `public/index.php` and blocks `/app`, `/config`, `/database`, `/storage`,
`/vendor`, dotfiles and `*.sql`/`*.sqlite`.

Set permissions: `storage/` and everything under it writable by PHP
(`0755`/`0775` dirs, `0644` files is usually enough on shared hosting; if the
host runs PHP as your user, `0700` is fine).

## 3. Configuration

1. Copy `config/config.local.php.example` to `config/config.local.php`.
2. Fill in:
   - `app.env` = `production`, `app.debug` = `false`
   - `app.url` = your https URL, no trailing slash
   - `app.key` = output of `php -r "echo 'base64:'.base64_encode(random_bytes(32));"`
   - `db.*` = the values from step 1
   - `mail.*` = your transactional SMTP provider (Brevo, Mailgun, …);
     set `mail.driver` = `smtp`
3. `config/config.local.php` must NOT be world-readable. On layout B it is also
   protected by the root `.htaccess`; on layout A it already sits outside the
   web root.

## 4. Schema + seed

The migrator connects with the same credentials as the app. Run it **from your
local machine** against the remote database (open the host's "remote MySQL"
allowance for your IP if required):

```
# with config/config.local.php pointing at the REMOTE db:
php database/migrate.php --seed
```

No remote-MySQL access? Export each `database/schema/*.sql` (in order) and each
`database/seed/*.sql`, and run them through **phpMyAdmin → SQL**. Then create
the `_migrations` rows manually so the migrator won't re-run them:

```sql
INSERT INTO _migrations (filename, applied_at) VALUES
 ('001_core.sql', UTC_TIMESTAMP()), ('002_identity.sql', UTC_TIMESTAMP()), ...;
```

## 5. First admin + data import

```
php database/make_admin.php --email=you@example.com --name="You" --super
php database/import_live_sqlite.php --dry-run     # review the plan
php database/import_live_sqlite.php --commit      # load the Mchinji farm data
```

`import_live_sqlite.php` reads `live.sqlite` from the project root; upload it
alongside the code for the import, then delete it from the server.

## 6. Post-deploy checks

- `https://your-domain/health` → `{"status":"ok","database":true,...}`
- `https://your-domain/` renders; `/login`, `/register` render
- Response headers include `Content-Security-Policy`, `X-Frame-Options: DENY`,
  `Strict-Transport-Security` (prod + https)
- Register a throwaway account → activation email arrives → activate → dashboard
- `storage/logs/` is being written; `storage/` is **not** listable over the web
  (`https://your-domain/storage/` → 403)
- `https://your-domain/config/config.local.php` → 403/404 (never 200)

## 7. Ongoing

- **No cron**: subscription expiry, notification generation, weather refresh and
  the mail queue all run opportunistically on normal page loads. A busy site
  needs nothing extra. A very quiet site can hit `/health` from an external
  uptime monitor every few minutes to keep the mail queue moving.
- **Backups**: schedule a daily dump in the host panel if available; otherwise
  export via phpMyAdmin weekly and before every schema change. Store the
  export **off the host** (a synced folder, email-to-self, whatever is
  reachable) — a backup that only lives next to the database it backs up
  doesn't survive the failure modes that matter (disk loss, account
  suspension, a bad migration).
- **Restore drill**: before trusting a backup, prove it restores. Locally:
  `mysql -u root new_db_name < your_export.sql`, then point
  `config/config.local.php` at `new_db_name` and confirm the app boots and
  shows real data. Do this once after the first backup and after any schema
  change large enough to worry about.
- **Updates**: upload changed files, then run `php database/migrate.php`
  (locally against remote, or new `*.sql` via phpMyAdmin) for any new
  migrations.

## 8. Go-live checklist

Run through this once, in order, before pointing real farmers at the site.

- [ ] `docs/SECURITY-REVIEW.md` read; the shared-hosting caveat in §"Known
      gaps" is an accepted, understood trade-off for this launch
- [ ] `config.local.php`: `app.env = production`, `app.debug = false`,
      a freshly generated `app.key` (not reused from local dev)
- [ ] `mail.driver = smtp` with real provider credentials — send yourself a
      test activation and password-reset email and confirm both arrive
- [ ] HTTPS confirmed working (padlock, `curl -I` shows the HSTS header from
      §6 above); HTTP requests redirect to HTTPS
- [ ] `php database/make_admin.php` run once for your own admin login; the
      password recorded somewhere safe (a password manager, not a note in
      the repo)
- [ ] Default/demo data reviewed: the seeded subscription tiers, market
      prices and CMS copy (`docs/`'s seed files) reflect real pricing and
      real marketing copy, not placeholders
- [ ] If importing `live.sqlite`: imported, `--dry-run` output reviewed
      first, then `live.sqlite` **deleted from the server** after import
      (it is not needed at runtime and contains real farm data)
- [ ] A first backup taken and restored locally per §7's drill
- [ ] `storage/`, `config/`, `database/`, `vendor/` confirmed not
      web-reachable from the live domain (the checks in §6 above)
- [ ] One end-to-end pass as a real user on the live domain: register →
      activate → onboarding → add a field → log an activity → add a
      transaction → view a record pack → sign out
- [ ] Support/contact email in `site_content` (`contact_email`) is one you
      actually monitor
