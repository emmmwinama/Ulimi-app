<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\CooperativeContext;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CooperativeRepository;
use App\Services\CooperativeStats;

final class CooperativeController extends Controller
{
    private const CONTRIBUTION_TYPES = ['membership', 'input_fund', 'equipment_fund', 'other'];

    public function __construct(
        private readonly CooperativeRepository $coops = new CooperativeRepository(),
        private readonly CooperativeStats $stats = new CooperativeStats(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('cooperative/index', [
            'title'       => 'Cooperatives',
            'active'      => 'cooperative',
            'cooperatives'=> $this->coops->forFarm($ctx->farmId()),
        ]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'name'   => ['required', 'max:160'],
            'region' => ['max:120'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->coops->create(
            $ctx->farmId(),
            (string) Auth::id(),
            trim((string) $data['name']),
            ($data['region'] ?? '') !== '' ? trim((string) $data['region']) : null,
        );
        AuditLog::user('cooperative.created', (string) Auth::id(), ['cooperative_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Cooperative created — you\'re its chair. Share the join code on the next page with other members.');
        return $this->redirect(url('cooperatives/' . $id));
    }

    public function join(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, ['join_code' => ['required', 'max:12']]);
        if ($data instanceof Response) {
            return $data;
        }
        $cooperative = $this->coops->findByJoinCode((string) $data['join_code']);
        if ($cooperative === null) {
            return $this->fieldError($request, 'join_code', 'No cooperative found with that code.');
        }
        if ($this->coops->membership((string) $cooperative['id'], $ctx->farmId()) !== null) {
            Flash::info('Your farm is already a member of that cooperative.');
            return $this->redirect(url('cooperatives/' . (string) $cooperative['id']));
        }
        $this->coops->addMember((string) $cooperative['id'], $ctx->farmId(), 'member');
        AuditLog::user('cooperative.joined', (string) Auth::id(), ['cooperative_id' => $cooperative['id']], $ctx->farmId(), $request->ip());
        Flash::success('Joined ' . (string) $cooperative['name'] . '.');
        return $this->redirect(url('cooperatives/' . (string) $cooperative['id']));
    }

    public function show(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        $coopId = $coopCtx->id();
        $memberFarmIds = $this->coops->memberFarmIds($coopId);

        return $this->view('cooperative/show', [
            'title'         => $coopCtx->name(),
            'active'        => 'cooperative',
            'coop'          => $coopCtx->cooperative,
            'role'          => $coopCtx->role(),
            'canManage'     => $coopCtx->canManage(),
            'members'       => $this->coops->members($coopId),
            'contributions' => $this->coops->contributions($coopId),
            'sales'         => $this->coops->sales($coopId),
            'inventoryRollup'   => $this->stats->inventoryRollup($memberFarmIds),
            'productionRollup'  => $this->stats->productionRollup($memberFarmIds),
            'contributionTypes' => self::CONTRIBUTION_TYPES,
        ]);
    }

    public function removeMember(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        if (!$coopCtx->canManage()) {
            Flash::error('Only the chair, secretary or treasurer can remove members.');
            return $this->redirect(url('cooperatives/' . $coopCtx->id()));
        }
        $farmId = (string) $request->route('farmId');
        if ($farmId === $coopCtx->member['farm_id']) {
            Flash::error('You can\'t remove your own farm here.');
            return $this->redirect(url('cooperatives/' . $coopCtx->id()));
        }
        $this->coops->removeMember($coopCtx->id(), $farmId);
        AuditLog::user('cooperative.member_removed', (string) Auth::id(), ['cooperative_id' => $coopCtx->id(), 'farm_id' => $farmId], FarmContext::current()->farmId(), $request->ip());
        Flash::success('Member removed.');
        return $this->redirect(url('cooperatives/' . $coopCtx->id()));
    }

    public function addContribution(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        if (!$coopCtx->canManage()) {
            Flash::error('Only the chair, secretary or treasurer can record contributions.');
            return $this->redirect(url('cooperatives/' . $coopCtx->id()));
        }

        $data = $this->validate($request, [
            'farm_id' => ['required'],
            'amount'  => ['required', 'numeric', 'min:0', 'max:100000000'],
            'date'    => ['required', 'date'],
            'type'    => ['required', 'in:' . implode(',', self::CONTRIBUTION_TYPES)],
            'notes'   => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        if ($this->coops->membership($coopCtx->id(), (string) $data['farm_id']) === null) {
            return $this->fieldError($request, 'farm_id', 'That farm isn\'t a member of this cooperative.');
        }

        $this->coops->addContribution($coopCtx->id(), (string) Auth::id(), [
            'farm_id' => (string) $data['farm_id'],
            'amount'  => round((float) $data['amount'], 2),
            'date'    => date('Y-m-d', (int) strtotime((string) $data['date'])),
            'type'    => (string) $data['type'],
            'notes'   => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
        ]);
        AuditLog::user('cooperative.contribution_added', (string) Auth::id(), ['cooperative_id' => $coopCtx->id()], FarmContext::current()->farmId(), $request->ip());
        Flash::success('Contribution recorded.');
        return $this->redirect(url('cooperatives/' . $coopCtx->id()));
    }

    public function addSale(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        if (!$coopCtx->canManage()) {
            Flash::error('Only the chair, secretary or treasurer can record a collective sale.');
            return $this->redirect(url('cooperatives/' . $coopCtx->id()));
        }

        $data = $this->validate($request, [
            'crop_name'      => ['required', 'max:120'],
            'total_quantity' => ['required', 'numeric', 'min:0.001', 'max:100000000'],
            'unit'           => ['max:20'],
            'price_per_unit' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'buyer_name'     => ['max:160'],
            'sale_date'      => ['required', 'date'],
            'notes'          => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $splits = $this->parseSplits($request, $coopCtx->id());
        $totalQty = (float) $data['total_quantity'];
        $price = (float) $data['price_per_unit'];
        $totalAmount = round($totalQty * $price, 2);

        $this->coops->addSale($coopCtx->id(), (string) Auth::id(), [
            'crop_name'      => trim((string) $data['crop_name']),
            'total_quantity' => $totalQty,
            'unit'           => ($data['unit'] ?? '') !== '' ? trim((string) $data['unit']) : 'kg',
            'price_per_unit' => $price,
            'total_amount'   => $totalAmount,
            'buyer_name'     => ($data['buyer_name'] ?? '') !== '' ? trim((string) $data['buyer_name']) : null,
            'sale_date'      => date('Y-m-d', (int) strtotime((string) $data['sale_date'])),
            'notes'          => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
        ], $splits);

        AuditLog::user('cooperative.sale_added', (string) Auth::id(), ['cooperative_id' => $coopCtx->id()], FarmContext::current()->farmId(), $request->ip());
        Flash::success('Collective sale recorded.');
        return $this->redirect(url('cooperatives/' . $coopCtx->id()));
    }

    /** @return list<array{farm_id:string,quantity:float,amount:float}> */
    private function parseSplits(Request $request, string $cooperativeId): array
    {
        $farmIds = (array) $request->input('split_farm_id', []);
        $qtys = (array) $request->input('split_quantity', []);
        $amounts = (array) $request->input('split_amount', []);

        $out = [];
        foreach ($farmIds as $i => $farmId) {
            $farmId = trim((string) $farmId);
            $qty = (float) ($qtys[$i] ?? 0);
            $amt = (float) ($amounts[$i] ?? 0);
            if ($farmId === '' || $qty <= 0) {
                continue;
            }
            if ($this->coops->membership($cooperativeId, $farmId) === null) {
                continue;
            }
            $out[] = ['farm_id' => $farmId, 'quantity' => $qty, 'amount' => round(max(0, $amt), 2)];
        }
        return $out;
    }
}
