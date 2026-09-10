<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Throwable;

final class SystemController extends Controller
{
    /**
     * Liveness + dependency check. Returns 200 only when the database is
     * reachable. No secrets, no version numbers in the body.
     */
    public function health(Request $request): Response
    {
        $dbOk = false;
        try {
            Database::instance()->scalar('SELECT 1');
            $dbOk = true;
        } catch (Throwable) {
            $dbOk = false;
        }

        return Response::json([
            'status'   => $dbOk ? 'ok' : 'degraded',
            'database' => $dbOk,
            'env'      => (string) Config::get('app.env'),
            'time'     => gmdate('c'),
        ], $dbOk ? 200 : 503);
    }
}
