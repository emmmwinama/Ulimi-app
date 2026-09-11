<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CropFieldRepository;
use App\Services\CreditScore;
use App\Services\FinanceSummary;
use App\Services\ReportBuilder;
use App\Services\Traceability;

final class ReportsController extends Controller
{
    /** Curated section sets for each record-pack type, in display order. */
    private const PACKS = [
        'loan'      => ['label' => 'Loan-readiness pack', 'sections' => ['fields', 'crops', 'activities', 'yields', 'finance', 'overheads', 'employees']],
        'buyer'     => ['label' => 'Buyer evidence pack', 'sections' => ['crops', 'activities', 'yields']],
        'audit'     => ['label' => 'Audit file', 'sections' => ['fields', 'crops', 'activities', 'finance', 'overheads', 'inventory', 'employees']],
        'insurance' => ['label' => 'Insurance file', 'sections' => ['fields', 'crops', 'activities', 'livestock']],
    ];

    public function __construct(
        private readonly ReportBuilder $builder = new ReportBuilder(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly CreditScore $creditScore = new CreditScore(),
        private readonly Traceability $traceability = new Traceability(),
        private readonly FinanceSummary $finance = new FinanceSummary(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('reports/index', [
            'title'   => 'Reports',
            'active'  => 'reports',
            'packs'   => self::PACKS,
            'canBuild'=> $ctx->feature('custom_reports'),
        ]);
    }

    /* ------------------------------------------------------------- trends */

    public function trends(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('reports/trends', [
            'title'   => 'Trends',
            'active'  => 'reports',
            'trend'   => $this->finance->monthlyTrend($ctx->farmId()),
            'totals'  => $this->finance->totals($ctx->farmId()),
        ]);
    }

    /* -------------------------------------------------------- record packs */

    public function pack(Request $request): Response
    {
        $ctx = FarmContext::current();
        $type = (string) $request->route('type');
        if (!isset(self::PACKS[$type])) {
            return $this->view('errors/404', [], 404);
        }

        $season = (string) $request->query('season', '');
        $sections = $this->builder->build($ctx->farmId(), self::PACKS[$type]['sections'], ['season' => $season]);

        AuditLog::user('report.pack_viewed', (string) Auth::id(), ['type' => $type], $ctx->farmId(), $request->ip());

        return $this->view('reports/pack', [
            'title'    => self::PACKS[$type]['label'],
            'active'   => 'reports',
            'packType' => $type,
            'packLabel'=> self::PACKS[$type]['label'],
            'farm'     => $ctx->farm,
            'sections' => $sections,
            'season'   => $season,
            'seasons'  => $this->crops->seasons($ctx->farmId()),
            'generatedAt' => gmdate('Y-m-d H:i'),
        ]);
    }

    /* ---------------------------------------------------------------- builder */

    public function builder(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->feature('custom_reports')) {
            Flash::error('Custom reports aren’t included in your current plan.');
            return $this->redirect(url('reports'));
        }

        $selected = (array) $request->query('sections', []);
        $season = (string) $request->query('season', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $sections = $selected !== [] ? $this->builder->build($ctx->farmId(), array_map('strval', $selected), [
            'season' => $season, 'from' => $from, 'to' => $to,
        ]) : [];

        return $this->view('reports/builder', [
            'title'     => 'Report builder',
            'active'    => 'reports',
            'available' => ReportBuilder::availableSections(),
            'selected'  => array_map('strval', $selected),
            'season'    => $season, 'from' => $from, 'to' => $to,
            'seasons'   => $this->crops->seasons($ctx->farmId()),
            'sections'  => $sections,
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->feature('custom_reports')) {
            Flash::error('Custom reports aren’t included in your current plan.');
            return $this->redirect(url('reports'));
        }

        $key = (string) $request->route('section');
        $selected = (array) $request->query('sections', []);
        if (!in_array($key, $selected, true)) {
            return Response::json(['error' => 'Section not in this report.'], 400);
        }

        $season = (string) $request->query('season', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $sections = $this->builder->build($ctx->farmId(), [$key], ['season' => $season, 'from' => $from, 'to' => $to]);

        if (!isset($sections[$key])) {
            return Response::json(['error' => 'Unknown section.'], 404);
        }

        AuditLog::user('report.csv_export', (string) Auth::id(), ['section' => $key], $ctx->farmId(), $request->ip());

        $csv = $this->builder->toCsv($sections[$key]);
        return Response::make($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $key . '-' . date('Y-m-d') . '.csv"');
    }

    /* ----------------------------------------------------------- compliance */

    public function compliance(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('reports/compliance', [
            'title'  => 'Compliance',
            'active' => 'reports',
            'checklist' => $this->traceability->complianceChecklist($ctx->farmId()),
            'lots'      => $this->traceability->lots($ctx->farmId()),
        ]);
    }

    /* ---------------------------------------------------------- credit score */

    public function creditScore(Request $request): Response
    {
        $ctx = FarmContext::current();
        $result = $this->creditScore->compute($ctx->farmId());
        return $this->view('reports/credit-score', [
            'title'   => 'Credit readiness',
            'active'  => 'reports',
            'result'  => $result,
            'history' => $this->creditScore->history($ctx->farmId(), 6),
        ]);
    }

    public function recomputeCreditScore(Request $request): Response
    {
        $ctx = FarmContext::current();
        $this->creditScore->persist($ctx->farmId(), (string) Auth::id());
        Flash::success('Credit-readiness score recalculated and saved.');
        return $this->redirect(url('reports/credit-score'));
    }
}
