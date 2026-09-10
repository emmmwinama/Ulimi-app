<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * Loaded once by public/index.php (and by CLI scripts under database/).
 * Responsibilities, in order:
 *   1. Define base paths.
 *   2. Register the PSR-4 autoloader for the App\ namespace + load helpers.
 *   3. Load configuration (defaults + config.local.php).
 *   4. Install the error / exception handler.
 *   5. Set the default timezone.
 *
 * It does NOT start a session or emit headers — HTTP concerns are the front
 * controller's job, so CLI scripts can reuse this file untouched.
 */

define('APP_START', microtime(true));

$root = dirname(__DIR__);

$paths = [
    'root'    => $root,
    'app'     => $root . '/app',
    'config'  => $root . '/config',
    'storage' => $root . '/storage',
    'views'   => $root . '/app/Views',
    'vendor'  => $root . '/vendor',
    'database'=> $root . '/database',
    'public'  => $root . '/public',
];

/* ---------------------------------------------------------------------------
 | 2. Autoloader + helpers
 |
 | Hand-rolled PSR-4: App\Foo\Bar  ->  app/Foo/Bar.php
 | (no Composer on the target host).
 * ------------------------------------------------------------------------- */
spl_autoload_register(static function (string $class) use ($paths): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $paths['app'] . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require $paths['app'] . '/Core/helpers.php';

/* ---------------------------------------------------------------------------
 | 3. Configuration
 * ------------------------------------------------------------------------- */
$config = require $paths['config'] . '/config.php';

$localFile = $paths['config'] . '/config.local.php';
if (is_file($localFile)) {
    /** @var array $local */
    $local = require $localFile;
    $config = \App\Core\Config::deepMerge($config, is_array($local) ? $local : []);
}

$config['paths'] = $paths;
$config['uploads']['path'] ??= null;
if ($config['uploads']['path'] === null) {
    $config['uploads']['path'] = $paths['storage'] . '/uploads';
}

\App\Core\Config::load($config);

/* ---------------------------------------------------------------------------
 | 4. Error handling
 * ------------------------------------------------------------------------- */
\App\Core\ErrorHandler::register(
    debug: (bool) \App\Core\Config::get('app.debug', false),
    logDir: $paths['storage'] . '/logs',
);

/* ---------------------------------------------------------------------------
 | 5. Timezone + baseline runtime hardening
 * ------------------------------------------------------------------------- */
date_default_timezone_set((string) \App\Core\Config::get('app.timezone', 'UTC'));

// Never advertise the PHP version.
if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
}
ini_set('expose_php', '0');

return \App\Core\Config::all();
