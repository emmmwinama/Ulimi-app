<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Convenience wrapper over Session flash storage. Levels map to UI styling in
 * the layout's alert partial.
 */
final class Flash
{
    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    private static function add(string $level, string $message): void
    {
        $session = Session::instance();
        $existing = $session->get('_flash', []);
        $bag = is_array($existing) ? $existing : [];
        $bag['messages'][] = ['level' => $level, 'text' => $message];
        $session->put('_flash', $bag);
    }

    /** @return list<array{level:string,text:string}> */
    public static function pull(): array
    {
        $now = Session::instance()->getFlash('messages', []);
        return is_array($now) ? $now : [];
    }
}
