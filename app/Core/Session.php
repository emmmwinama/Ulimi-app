<?php

declare(strict_types=1);

namespace App\Core;

use SessionHandlerInterface;

/**
 * Hardened session facade backed by a database handler.
 *
 * Why a DB handler: on shared hosting the default file session store lives in a
 * directory other tenants can often read. Storing the payload in our own table
 * removes that exposure and lets us bind a session to its IP + user agent.
 *
 * Security properties:
 *   - cookie: HttpOnly, SameSite=Lax, Secure (when the request is HTTPS)
 *   - session.use_strict_mode = 1  (reject attacker-supplied IDs)
 *   - id rotated on privilege change (login) and periodically
 *   - idle timeout + absolute lifetime enforced on boot
 *   - payload bound to a UA fingerprint; mismatch => session dropped
 */
final class Session
{
    private static ?self $instance = null;
    private bool $started = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(Request $request): void
    {
        if ($this->started || PHP_SAPI === 'cli') {
            $this->started = true;
            return;
        }

        $handler = new DatabaseSessionHandler(Database::instance());
        session_set_save_handler($handler, true);

        $secure = $request->isSecure() && (bool) Config::get('session.cookie_secure', true);

        session_name((string) Config::get('session.name', 'agv_sess'));
        session_set_cookie_params([
            'lifetime' => 0, // session cookie; server-side TTL is authoritative
            'path'     => (string) Config::get('session.cookie_path', '/'),
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => (string) Config::get('session.cookie_samesite', 'Lax'),
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '5');
        ini_set('session.gc_maxlifetime', (string) (int) Config::get('session.lifetime', 28800));

        session_start();
        $this->started = true;

        $this->enforceTimeouts();
        $this->enforceFingerprint($request);
        $this->rotatePeriodically();
    }

    /* ----------------------------------------------------------------- data */

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /* --------------------------------------------------------------- flash */

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_now'][$key] ?? $default;
    }

    /** @return array<string,mixed> */
    public function allFlash(): array
    {
        return $_SESSION['_flash_now'] ?? [];
    }

    /** Move this-request flashes out, promote next-request flashes in. Called once per request. */
    public function ageFlash(): void
    {
        $_SESSION['_flash_now'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
    }

    /**
     * Flash the just-submitted input so a redirect-back form can repopulate.
     * Sensitive fields are dropped. Lives one request, via the flash cycle.
     *
     * @param array<string,mixed> $input
     */
    public function flashInput(array $input): void
    {
        foreach (['password', 'password_confirmation', 'current_password', '_token', '_method'] as $drop) {
            unset($input[$drop]);
        }
        $_SESSION['_flash']['_old'] = $input;
    }

    /** @return array<string,mixed> */
    public function oldInput(): array
    {
        $old = $this->getFlash('_old', []);
        return is_array($old) ? $old : [];
    }

    /* ---------------------------------------------------------------- csrf */

    public function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /* ------------------------------------------------------------- session id */

    public function regenerate(bool $deleteOld = true): void
    {
        if ($this->started && PHP_SAPI !== 'cli') {
            session_regenerate_id($deleteOld);
            $_SESSION['_rotated_at'] = time();
        }
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        if (PHP_SAPI !== 'cli' && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }
        if ($this->started && PHP_SAPI !== 'cli') {
            session_destroy();
        }
        $this->started = false;
    }

    /* --------------------------------------------------------------- guards */

    private function enforceTimeouts(): void
    {
        $now = time();
        $idle = (int) Config::get('session.idle_timeout', 7200);
        $absolute = (int) Config::get('session.lifetime', 28800);

        $lastSeen = (int) ($_SESSION['_last_seen'] ?? $now);
        $createdAt = (int) ($_SESSION['_created_at'] ?? $now);

        if (($now - $lastSeen) > $idle || ($now - $createdAt) > $absolute) {
            $this->invalidate();
            session_start();
            $_SESSION['_created_at'] = $now;
        }

        $_SESSION['_created_at'] ??= $now;
        $_SESSION['_last_seen'] = $now;
    }

    private function enforceFingerprint(Request $request): void
    {
        // User agent only — IP churns legitimately on mobile networks, which is
        // the primary client here, so binding to IP would cause false logouts.
        $fingerprint = hash('sha256', $request->userAgent() . '|' . (string) Config::get('app.key', ''));

        if (!isset($_SESSION['_fp'])) {
            $_SESSION['_fp'] = $fingerprint;
            return;
        }
        if (!hash_equals($_SESSION['_fp'], $fingerprint)) {
            $this->invalidate();
            session_start();
            $_SESSION['_fp'] = $fingerprint;
            $_SESSION['_created_at'] = time();
        }
    }

    private function rotatePeriodically(): void
    {
        $every = (int) Config::get('session.regenerate_every', 900);
        $rotatedAt = (int) ($_SESSION['_rotated_at'] ?? 0);
        if ((time() - $rotatedAt) > $every) {
            $this->regenerate(true);
        }
    }
}
