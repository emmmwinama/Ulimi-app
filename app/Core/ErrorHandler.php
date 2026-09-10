<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Converts PHP errors to exceptions, logs uncaught throwables, and renders a
 * safe response. In debug mode it shows the detail; in production it shows a
 * generic page and nothing else (no stack traces, no messages).
 */
final class ErrorHandler
{
    private static bool $registered = false;
    private static bool $debug = false;
    private static string $logDir = '';

    public static function register(bool $debug, string $logDir): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        self::$debug = $debug;
        self::$logDir = $logDir;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /** @throws ErrorException */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false; // suppressed with @ or below threshold
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        Logger::init(self::$logDir)->error('Uncaught {class}: {message} @ {file}:{line}', [
            'class'   => $e::class,
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => self::$debug ? $e->getTraceAsString() : null,
        ]);

        self::renderFatal($e);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }
        if (($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) === 0) {
            return;
        }

        Logger::init(self::$logDir)->error('Fatal {type}: {message} @ {file}:{line}', $error + ['type' => $error['type']]);
        self::renderFatal(new ErrorException(
            $error['message'], 0, $error['type'], $error['file'], $error['line']
        ));
    }

    private static function renderFatal(Throwable $e): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        // Clear any partial output so we never leak a half-rendered page.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, '[' . $e::class . '] ' . $e->getMessage()
                . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
            if (self::$debug) {
                fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
            }
            exit(1);
        }

        if (self::$debug) {
            echo '<!doctype html><meta charset="utf-8"><title>Application error</title>';
            echo '<div style="font:14px/1.5 ui-monospace,Menlo,Consolas,monospace;padding:24px;max-width:960px;margin:0 auto">';
            echo '<h1 style="font-size:18px">' . e($e::class) . '</h1>';
            echo '<p style="color:#b91c1c">' . e($e->getMessage()) . '</p>';
            echo '<p style="color:#64748b">' . e($e->getFile()) . ':' . e((string) $e->getLine()) . '</p>';
            echo '<pre style="white-space:pre-wrap;background:#f8fafc;padding:16px;border-radius:8px;overflow:auto">'
                . e($e->getTraceAsString()) . '</pre></div>';
            exit(1);
        }

        $template = self::$logDir . '/../../app/Views/errors/500.php';
        if (is_file($template)) {
            include $template;
        } else {
            echo '<!doctype html><meta charset="utf-8"><title>Something went wrong</title>'
                . '<p style="font:16px/1.5 system-ui;padding:40px;text-align:center">'
                . 'Something went wrong on our end. Please try again shortly.</p>';
        }
        exit(1);
    }
}
