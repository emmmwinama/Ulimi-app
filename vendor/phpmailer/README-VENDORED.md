# Vendored: PHPMailer

- **Version:** 6.9.1 (pinned to the official tag)
- **Source:** https://github.com/PHPMailer/PHPMailer
- **License:** LGPL 2.1 — see `LICENSE` in this directory
- **Files:** `src/PHPMailer.php`, `src/SMTP.php`, `src/Exception.php` only
  (the three files the SMTP send path in `App\Core\Mailer` actually uses;
  PHPMailer's optional POP-before-SMTP and DKIM helpers were left out)

No Composer on the target host, so this is a manual, pinned vendor drop
rather than a `composer require`. Autoloaded via the `PHPMailer\PHPMailer\`
prefix registered alongside the app's own `App\` prefix in
`app/bootstrap.php`.

To update: replace the three files in `src/` with the same files from a
newer tagged release, re-run `php -l` on each, and confirm
`App\Core\Mailer`'s SMTP path still sends (there is no automated test for an
actual SMTP send — verify manually against a real or sandbox mail provider).
