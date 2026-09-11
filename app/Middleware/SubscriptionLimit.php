<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Blocks a "create" route once the active farm has hit its subscription
 * tier's limit for that resource. Route spec: `SubscriptionLimit:fields`.
 *
 * -1 on the tier means unlimited. A tier row missing entirely (should not
 * happen, FarmContext always resolves one) fails open on read but this
 * middleware only ever runs on write routes already gated by Can:*.manage,
 * so the worst case is a blocked create, never data exposure.
 */
final class SubscriptionLimit implements Middleware
{
    /** resource key => [table, limit column, extra WHERE] */
    private const MAP = [
        'fields'     => ['fields', 'max_fields', ''],
        'crops'      => ['crop_fields', 'max_crops', 'AND is_archived = 0'],
        'activities' => ['farm_activities', 'max_activities', ''],
        'finance'    => ['transactions', 'max_transactions', ''],
        'employees'  => ['employees', 'max_employees', ''],
        'team'       => ['farm_members', 'max_team_members', "AND status = 'active'"],
    ];

    public function __construct(private readonly string $resource = '')
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!isset(self::MAP[$this->resource])) {
            return $next($request);
        }
        [$table, $limitCol, $extraWhere] = self::MAP[$this->resource];

        $ctx = FarmContext::current();
        $limit = $ctx->limit($limitCol);

        if ($limit === null || $limit === -1) {
            return $next($request);
        }

        $count = (int) Database::instance()->scalar(
            "SELECT COUNT(*) FROM {$table} WHERE farm_id = :fid {$extraWhere}",
            ['fid' => $ctx->farmId()],
        );

        if ($count >= $limit) {
            $message = "Your plan allows up to {$limit} " . $this->resource . ". Upgrade to add more.";
            if ($request->wantsJson()) {
                return Response::json(['error' => $message], 403);
            }
            return Response::view('errors/403', ['message' => $message], 403);
        }

        return $next($request);
    }
}
