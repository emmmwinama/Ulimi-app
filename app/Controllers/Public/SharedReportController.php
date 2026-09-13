<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\FarmRepository;
use App\Repositories\ReportShareLinkRepository;
use App\Services\ReportBuilder;

/**
 * The one deliberately unauthenticated read path in the app (Phase 18):
 * a farmer-generated, expiring, revocable link to one record pack, shared
 * with a lender/buyer/NGO/etc. by consent. Trust comes entirely from the
 * token (256 bits, hashed at rest — see ReportShareLinkRepository); the
 * viewer's identity is never checked, matching a normal "share link".
 */
final class SharedReportController extends Controller
{
    /** Packs, mirrored from ReportsController — kept small and local since
     *  this controller must never depend on farm-authenticated state. */
    private const PACKS = [
        'loan'      => ['label' => 'Loan-readiness pack', 'purpose' => 'Loan / credit application supporting evidence', 'sections' => ['fields', 'crops', 'activities', 'yields', 'pnl', 'overheads', 'employees']],
        'buyer'     => ['label' => 'Buyer evidence pack', 'purpose' => 'Buyer traceability and production evidence', 'sections' => ['crops', 'activities', 'yields']],
        'audit'     => ['label' => 'Audit file', 'purpose' => 'Internal or third-party audit file', 'sections' => ['fields', 'crops', 'activities', 'finance', 'overheads', 'inventory', 'employees']],
        'insurance' => ['label' => 'Insurance file', 'purpose' => 'Insurance claim or underwriting evidence', 'sections' => ['fields', 'crops', 'activities', 'livestock', 'climate_events']],
    ];

    public function __construct(
        private readonly ReportShareLinkRepository $shareLinks = new ReportShareLinkRepository(),
        private readonly FarmRepository $farms = new FarmRepository(),
        private readonly ReportBuilder $builder = new ReportBuilder(),
    ) {
    }

    public function show(Request $request): Response
    {
        $token = (string) $request->route('token');

        // Surgical rate limit on *failed* lookups only, keyed by IP — a valid
        // link can be reopened freely, but guessing is throttled. The generic
        // Throttle middleware only counts non-GET requests, which doesn't fit
        // this GET-only route, hence a manual bucket here.
        $limiter = RateLimiter::instance();
        $bucket = 'shared_report:' . $request->ip();
        if ($limiter->tooManyAttempts($bucket, 20)) {
            return $this->view('errors/429', ['minutes' => 15], 429);
        }

        $link = $this->shareLinks->resolveActive($token);
        if ($link === null) {
            $limiter->hit($bucket, 900);
            return $this->view('errors/404', [], 404);
        }

        $packType = (string) $link['pack_type'];
        if (!isset(self::PACKS[$packType])) {
            return $this->view('errors/404', [], 404);
        }

        $farm = $this->farms->find((string) $link['farm_id']);
        if ($farm === null) {
            return $this->view('errors/404', [], 404);
        }

        $this->shareLinks->recordView((string) $link['id']);

        $sections = $this->builder->build((string) $farm['id'], self::PACKS[$packType]['sections']);

        return $this->view('reports/pack', [
            'title'       => self::PACKS[$packType]['label'],
            'packType'    => $packType,
            'packLabel'   => self::PACKS[$packType]['label'],
            'purpose'     => self::PACKS[$packType]['purpose'],
            'farm'        => $farm,
            'farmName'    => (string) $farm['name'],
            'sections'    => $sections,
            'season'      => '',
            'seasons'     => [],
            'dateRange'   => 'All seasons',
            'generatedAt' => gmdate('Y-m-d H:i'),
            'public'      => true,
            'shareLinks'  => [],
            'canShare'    => false,
        ]);
    }
}
