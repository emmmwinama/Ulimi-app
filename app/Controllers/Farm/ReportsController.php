<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CropFieldRepository;
use App\Repositories\FieldRepository;
use App\Services\Ai;
use App\Services\CreditScore;
use App\Services\FinanceSummary;
use App\Services\ReportBuilder;
use App\Services\ReportsAnalytics;
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

    private const DASHBOARD_TABS = [
        'overview', 'crops', 'finance', 'analytics', 'yields',
        'overhead', 'trends', 'performance', 'breakeven', 'comparison',
    ];

    public function __construct(
        private readonly ReportBuilder $builder = new ReportBuilder(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly CreditScore $creditScore = new CreditScore(),
        private readonly Traceability $traceability = new Traceability(),
        private readonly FinanceSummary $finance = new FinanceSummary(),
        private readonly ReportsAnalytics $analytics = new ReportsAnalytics(),
        private readonly Ai $ai = new Ai(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $farmId = $ctx->farmId();
        $db = Database::instance();

        $tab = (string) $request->query('tab', 'overview');
        if (!in_array($tab, self::DASHBOARD_TABS, true)) {
            $tab = 'overview';
        }

        $filters = [
            'season'        => (string) $request->query('season', ''),
            'archived'      => (string) $request->query('archived', 'active'),
            'field_id'      => (string) $request->query('field_id', ''),
            'crop_field_id' => (string) $request->query('crop_field_id', ''),
            'from'          => (string) $request->query('from', ''),
            'to'            => (string) $request->query('to', ''),
        ];
        if (!in_array($filters['archived'], ['active', 'archived', 'both'], true)) {
            $filters['archived'] = 'active';
        }

        $dashboard = $this->analytics->build($farmId, $filters);

        $allSeasons = $dashboard['trends']['season_summaries'];
        $compareA = (string) $request->query('compare_a', '');
        $compareB = (string) $request->query('compare_b', '');
        $seasonKeys = array_keys($allSeasons);
        if ($compareA === '' || !isset($allSeasons[$compareA])) {
            $compareA = $seasonKeys !== [] ? $seasonKeys[count($seasonKeys) - 1] : '';
        }
        if ($compareB === '' && count($seasonKeys) >= 2) {
            $compareB = $seasonKeys[count($seasonKeys) - 2];
        }

        $totals = $this->finance->totals($farmId);
        $trend = $this->finance->monthlyTrend($farmId);

        $yieldKg = (float) $db->scalar(
            "SELECT COALESCE(SUM(hy.quantity_kg), 0) FROM harvest_yields hy
             JOIN crop_fields cf ON cf.id = hy.crop_field_id WHERE cf.farm_id = :fid",
            ['fid' => $farmId],
        );

        // Season net revenue (income + activity cost, matching the dashboard's
        // own season math) for the profitability bars.
        $seasonMap = [];
        foreach ($db->select(
            "SELECT season, type, COALESCE(SUM(amount), 0) AS amt
             FROM transactions WHERE farm_id = :fid AND season IS NOT NULL AND season != ''
             GROUP BY season, type",
            ['fid' => $farmId],
        ) as $row) {
            $seasonMap[$row['season']] ??= ['income' => 0.0, 'expense' => 0.0];
            if ($row['type'] === 'Income') {
                $seasonMap[$row['season']]['income'] = (float) $row['amt'];
            } else {
                $seasonMap[$row['season']]['expense'] = (float) $row['amt'];
            }
        }
        $seasons = [];
        foreach ($seasonMap as $name => $s) {
            $seasons[] = ['name' => (string) $name, 'net' => $s['income'] - $s['expense']];
        }
        usort($seasons, static fn ($a, $b) => strcmp((string) $b['name'], (string) $a['name']));
        $seasons = array_slice($seasons, 0, 6);

        // Crop area mix for the same period.
        $cropRows = $db->select(
            "SELECT ct.name, COALESCE(SUM(cf.area_planted), 0) AS area
             FROM crop_fields cf JOIN crop_types ct ON ct.id = cf.crop_type_id
             WHERE cf.farm_id = :fid AND cf.is_archived = 0
             GROUP BY ct.name ORDER BY area DESC LIMIT 6",
            ['fid' => $farmId],
        );

        $insight = $this->ai->insight(
            $farmId,
            'reports_overview',
            'Summarise this farm\'s overall financial and production performance for its owner: '
                . 'highlight whether it is profitable, the biggest cost driver, and one concrete '
                . 'suggestion grounded in the numbers given.',
            [
                'income' => $totals['income'],
                'total_cost' => $totals['total_cost'],
                'net' => $totals['net'],
                'cost_breakdown' => [
                    'expense_transactions' => $totals['expense_tx'],
                    'activity_costs' => $totals['activity_cost'],
                    'overheads' => $totals['overhead'],
                ],
                'top_expense_categories' => array_slice($totals['by_category'], 0, 5, true),
                'total_yield_kg' => $yieldKg,
                'season_net_revenue' => $seasons,
                'crop_area_mix_ha' => $cropRows,
                'currency' => 'MWK',
            ],
        );

        return $this->view('reports/index', [
            'title'      => 'Reports',
            'active'     => 'reports',
            'tab'        => $tab,
            'filters'    => $filters,
            'dashboard'  => $dashboard,
            'compareA'   => $compareA,
            'compareB'   => $compareB,
            'filterFields' => $this->fields->forFarm($farmId),
            'filterCrops'  => array_merge(
                $this->crops->forFarm($farmId, ['archived' => false]),
                $this->crops->forFarm($farmId, ['archived' => true]),
            ),
            'packs'    => self::PACKS,
            'canBuild' => $ctx->feature('custom_reports'),
            'totals'   => $totals,
            'trend'    => $trend,
            'yieldKg'  => $yieldKg,
            'seasons'  => $seasons,
            'crops'    => $cropRows,
            'insight'  => $insight,
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
