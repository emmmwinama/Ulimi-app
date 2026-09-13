<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\CooperativeContext;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CooperativeRepository;
use App\Services\CooperativeStats;

/**
 * Permission model here is role-based in-controller (chair/secretary/treasurer
 * via CooperativeContext::canManage()), not a route Can:* middleware — same
 * as the web controller. Routes for show/removeMember/addContribution/addSale
 * carry the ResolveCooperativeContext middleware so CooperativeContext::current()
 * is already populated by the time these methods run.
 */
final class ApiCooperativeController
{
    private const CONTRIBUTION_TYPES = ['membership', 'input_fund', 'equipment_fund', 'other'];

    public function __construct(
        private readonly CooperativeRepository $coops = new CooperativeRepository(),
        private readonly CooperativeStats $stats = new CooperativeStats(),
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::json(['data' => $this->coops->forFarm(FarmContext::current()->farmId())]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $v = Validator::make($request->all(), [
            'name' => ['required', 'max:160'],
            'region' => ['max:120'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $id = $this->coops->create(
            $ctx->farmId(),
            (string) ApiAuth::id(),
            trim((string) $d['name']),
            ($d['region'] ?? '') !== '' ? trim((string) $d['region']) : null,
        );
        AuditLog::user('api.cooperative.created', (string) ApiAuth::id(), ['cooperative_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => ['id' => $id]], 201);
    }

    public function join(Request $request): Response
    {
        $ctx = FarmContext::current();
        $v = Validator::make($request->all(), ['join_code' => ['required', 'max:12']]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $cooperative = $this->coops->findByJoinCode((string) $d['join_code']);
        if ($cooperative === null) {
            return Response::json(['errors' => ['join_code' => ['No cooperative found with that code.']]], 422);
        }
        if ($this->coops->membership((string) $cooperative['id'], $ctx->farmId()) !== null) {
            return Response::json(['data' => $cooperative, 'note' => 'Your farm is already a member of that cooperative.']);
        }
        $this->coops->addMember((string) $cooperative['id'], $ctx->farmId(), 'member');
        AuditLog::user('api.cooperative.joined', (string) ApiAuth::id(), ['cooperative_id' => $cooperative['id']], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $cooperative], 201);
    }

    public function show(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        $coopId = $coopCtx->id();
        $memberFarmIds = $this->coops->memberFarmIds($coopId);

        return Response::json(['data' => [
            'cooperative' => $coopCtx->cooperative,
            'role' => $coopCtx->role(),
            'can_manage' => $coopCtx->canManage(),
            'members' => $this->coops->members($coopId),
            'contributions' => $this->coops->contributions($coopId),
            'sales' => $this->coops->sales($coopId),
            'inventory_rollup' => $this->stats->inventoryRollup($memberFarmIds),
            'production_rollup' => $this->stats->productionRollup($memberFarmIds),
        ]]);
    }

    public function removeMember(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        if (!$coopCtx->canManage()) {
            return Response::json(['error' => 'Only the chair, secretary or treasurer can remove members.'], 403);
        }
        $farmId = (string) $request->route('farmId');
        if ($farmId === $coopCtx->member['farm_id']) {
            return Response::json(['error' => 'You can\'t remove your own farm here.'], 422);
        }
        $this->coops->removeMember($coopCtx->id(), $farmId);
        AuditLog::user('api.cooperative.member_removed', (string) ApiAuth::id(), ['cooperative_id' => $coopCtx->id(), 'farm_id' => $farmId], FarmContext::current()->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    public function addContribution(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        if (!$coopCtx->canManage()) {
            return Response::json(['error' => 'Only the chair, secretary or treasurer can record contributions.'], 403);
        }

        $v = Validator::make($request->all(), [
            'farm_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:' . implode(',', self::CONTRIBUTION_TYPES)],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        if ($this->coops->membership($coopCtx->id(), (string) $d['farm_id']) === null) {
            return Response::json(['errors' => ['farm_id' => ['That farm isn\'t a member of this cooperative.']]], 422);
        }

        $id = $this->coops->addContribution($coopCtx->id(), (string) ApiAuth::id(), [
            'farm_id' => (string) $d['farm_id'],
            'amount' => round((float) $d['amount'], 2),
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'type' => (string) $d['type'],
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ]);
        AuditLog::user('api.cooperative.contribution_added', (string) ApiAuth::id(), ['cooperative_id' => $coopCtx->id()], FarmContext::current()->farmId(), $request->ip());
        return Response::json(['data' => ['id' => $id]], 201);
    }

    public function addSale(Request $request): Response
    {
        $coopCtx = CooperativeContext::current();
        if (!$coopCtx->canManage()) {
            return Response::json(['error' => 'Only the chair, secretary or treasurer can record a collective sale.'], 403);
        }

        $v = Validator::make($request->all(), [
            'crop_name' => ['required', 'max:120'],
            'total_quantity' => ['required', 'numeric', 'min:0.001', 'max:100000000'],
            'unit' => ['max:20'],
            'price_per_unit' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'buyer_name' => ['max:160'],
            'sale_date' => ['required', 'date'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $splits = $this->parseSplits($request, $coopCtx->id());
        $totalQty = (float) $d['total_quantity'];
        $price = (float) $d['price_per_unit'];
        $totalAmount = round($totalQty * $price, 2);

        $id = $this->coops->addSale($coopCtx->id(), (string) ApiAuth::id(), [
            'crop_name' => trim((string) $d['crop_name']),
            'total_quantity' => $totalQty,
            'unit' => ($d['unit'] ?? '') !== '' ? trim((string) $d['unit']) : 'kg',
            'price_per_unit' => $price,
            'total_amount' => $totalAmount,
            'buyer_name' => ($d['buyer_name'] ?? '') !== '' ? trim((string) $d['buyer_name']) : null,
            'sale_date' => date('Y-m-d', (int) strtotime((string) $d['sale_date'])),
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ], $splits);

        AuditLog::user('api.cooperative.sale_added', (string) ApiAuth::id(), ['cooperative_id' => $coopCtx->id()], FarmContext::current()->farmId(), $request->ip());
        return Response::json(['data' => ['id' => $id]], 201);
    }

    /**
     * Accepts either JSON `splits: [{farm_id,quantity,amount}, ...]` or the
     * web's parallel-array shape (split_farm_id[]/split_quantity[]/split_amount[])
     * so the same endpoint works whether the client posts form-style or JSON.
     *
     * @return list<array{farm_id:string,quantity:float,amount:float}>
     */
    private function parseSplits(Request $request, string $cooperativeId): array
    {
        $rows = [];
        $splits = $request->input('splits');
        if (is_array($splits)) {
            foreach ($splits as $row) {
                $rows[] = [
                    'farm_id' => (string) ($row['farm_id'] ?? ''),
                    'quantity' => (float) ($row['quantity'] ?? 0),
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            }
        } else {
            $farmIds = (array) $request->input('split_farm_id', []);
            $qtys = (array) $request->input('split_quantity', []);
            $amounts = (array) $request->input('split_amount', []);
            foreach ($farmIds as $i => $farmId) {
                $rows[] = [
                    'farm_id' => trim((string) $farmId),
                    'quantity' => (float) ($qtys[$i] ?? 0),
                    'amount' => (float) ($amounts[$i] ?? 0),
                ];
            }
        }

        $out = [];
        foreach ($rows as $row) {
            if ($row['farm_id'] === '' || $row['quantity'] <= 0) {
                continue;
            }
            if ($this->coops->membership($cooperativeId, $row['farm_id']) === null) {
                continue;
            }
            $out[] = ['farm_id' => $row['farm_id'], 'quantity' => $row['quantity'], 'amount' => round(max(0, $row['amount']), 2)];
        }
        return $out;
    }
}
