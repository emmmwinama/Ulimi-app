<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SubscriptionRepository;

final class AdminTiersController extends Controller
{
    private const LIMIT_FIELDS = ['max_fields', 'max_crops', 'max_activities', 'max_transactions', 'max_employees', 'max_farms', 'max_team_members'];
    private const FEATURE_FIELDS = ['season_analytics', 'yield_suggestions', 'cost_per_hectare', 'payroll_tracking', 'multiple_farms', 'team_accounts', 'custom_reports', 'api_access', 'sync_enabled', 'data_retention_lifetime'];

    public function __construct(private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository())
    {
    }

    public function index(Request $request): Response
    {
        return $this->view('admin/tiers/index', [
            'title' => 'Tiers',
            'tiers' => $this->subscriptions->allTiers(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin/tiers/form', ['title' => 'Add tier', 'tier' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->prepared($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->subscriptions->createTier($data);
        AuditLog::admin('admin.tier_created', (string) AdminAuth::id(), 'tier', $id, [], $request->ip());
        Flash::success('Tier created.');
        return $this->redirect(url('admin/tiers'));
    }

    public function edit(Request $request): Response
    {
        $tier = $this->subscriptions->findTier((string) $request->route('id'));
        if ($tier === null) {
            Flash::error('Tier not found.');
            return $this->redirect(url('admin/tiers'));
        }
        return $this->view('admin/tiers/form', ['title' => 'Edit tier', 'tier' => $tier]);
    }

    public function update(Request $request): Response
    {
        $id = (string) $request->route('id');
        if ($this->subscriptions->findTier($id) === null) {
            Flash::error('Tier not found.');
            return $this->redirect(url('admin/tiers'));
        }
        $data = $this->prepared($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->subscriptions->updateTier($id, $data);
        AuditLog::admin('admin.tier_updated', (string) AdminAuth::id(), 'tier', $id, [], $request->ip());
        Flash::success('Tier updated.');
        return $this->redirect(url('admin/tiers'));
    }

    public function destroy(Request $request): Response
    {
        $id = (string) $request->route('id');
        if ($this->subscriptions->tierInUse($id)) {
            Flash::error('This tier has active subscriptions and can’t be deleted. Deactivate it instead.');
            return $this->redirect(url('admin/tiers'));
        }
        $this->subscriptions->deleteTier($id);
        AuditLog::admin('admin.tier_deleted', (string) AdminAuth::id(), 'tier', $id, [], $request->ip());
        Flash::success('Tier deleted.');
        return $this->redirect(url('admin/tiers'));
    }

    /** @return array<string,mixed>|Response */
    private function prepared(Request $request): array|Response
    {
        $data = $this->validate($request, [
            'name'           => ['required', 'max:80'],
            'description'    => ['max:255'],
            'currency'       => ['required', 'max:3'],
            'price_monthly'  => ['required', 'numeric', 'min:0'],
            'price_annual'   => ['numeric', 'min:0'],
            'sort_order'     => ['integer'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $out = [
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'currency' => strtoupper((string) $data['currency']),
            'price_monthly' => (float) $data['price_monthly'],
            'price_annual' => ($data['price_annual'] ?? '') !== '' ? (float) $data['price_annual'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active') ? 1 : 0,
            'is_public' => $request->boolean('is_public') ? 1 : 0,
            'is_featured' => $request->boolean('is_featured') ? 1 : 0,
        ];
        foreach (self::LIMIT_FIELDS as $f) {
            $v = $request->input($f, '1');
            $out[$f] = (int) ($v === '' ? -1 : $v);
        }
        foreach (self::FEATURE_FIELDS as $f) {
            $out[$f] = $request->boolean($f) ? 1 : 0;
        }
        return $out;
    }
}
