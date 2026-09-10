<?php

declare(strict_types=1);

/**
 * Create or update a back-office administrator.
 *
 *   php database/make_admin.php --email=you@example.com --name="Your Name" [--super] [--password=...]
 *
 * If --password is omitted a strong one is generated and printed once.
 * Re-running for an existing email updates the name / super flag / password.
 */

use App\Core\Auth;
use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

require dirname(__DIR__) . '/app/bootstrap.php';

$opts = getopt('', ['email:', 'name:', 'password::', 'super']);

$email = isset($opts['email']) ? mb_strtolower(trim((string) $opts['email'])) : '';
$name  = isset($opts['name']) ? trim((string) $opts['name']) : '';
$super = array_key_exists('super', $opts);

if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || $name === '') {
    fwrite(STDERR, "Usage: php database/make_admin.php --email=you@example.com --name=\"Your Name\" [--super] [--password=...]\n");
    exit(1);
}

$password = isset($opts['password']) && $opts['password'] !== false && $opts['password'] !== ''
    ? (string) $opts['password']
    : bin2hex(random_bytes(9)); // 18-char generated password
$generated = !isset($opts['password']) || $opts['password'] === false || $opts['password'] === '';

if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

$db = Database::instance();
$existing = $db->selectOne('SELECT id FROM admin_users WHERE email = :e LIMIT 1', ['e' => $email]);

if ($existing !== null) {
    $db->update('admin_users', [
        'name'           => $name,
        'password'       => Auth::hash($password),
        'is_super_admin' => $super ? 1 : 0,
    ], ['id' => $existing['id']]);
    fwrite(STDOUT, "Updated admin: {$email}\n");
} else {
    $db->insert('admin_users', [
        'id'             => Ulid::generate(),
        'email'          => $email,
        'password'       => Auth::hash($password),
        'name'           => $name,
        'is_super_admin' => $super ? 1 : 0,
        'last_login_at'  => null,
        'created_at'     => Dates::nowUtc(),
    ]);
    fwrite(STDOUT, "Created admin: {$email}\n");
}

if ($generated) {
    fwrite(STDOUT, "Generated password (store it now, it will not be shown again):\n\n    {$password}\n\n");
}
fwrite(STDOUT, "Super admin: " . ($super ? 'yes' : 'no') . "\n");
