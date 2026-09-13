<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\BuyerOfferRepository;
use App\Repositories\BuyerRepository;
use App\Repositories\MarketPriceRepository;

/** Permission piggybacks on crops.manage/.view, same quirk as the web controller. */
final class ApiMarketController
{
    private const BUYER_TYPES = ['individual', 'company', 'cooperative', 'other'];
    private const OFFER_STATUSES = ['open', 'accepted', 'declined', 'expired'];

    public function __construct(
        private readonly MarketPriceRepository $prices = new MarketPriceRepository(),
        private readonly BuyerRepository $buyers = new BuyerRepository(),
        private readonly BuyerOfferRepository $offers = new BuyerOfferRepository(),
    ) {
    }

    /** Read-only reference data — no farm scoping, no permission check. */
    public function prices(Request $request): Response
    {
        $crop = (string) $request->query('crop', '');
        return Response::json(['data' => [
            'prices' => $this->prices->active($crop !== '' ? $crop : null),
            'crops' => $this->prices->crops(),
        ]]);
    }

    public function buyers(Request $request): Response
    {
        return Response::json(['data' => $this->buyers->forFarm(FarmContext::current()->farmId())]);
    }

    public function storeBuyer(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $v = Validator::make($request->all(), [
            'name' => ['required', 'max:160'],
            'type' => ['required', 'in:' . implode(',', self::BUYER_TYPES)],
            'phone' => ['max:40'],
            'email' => ['email', 'max:160'],
            'location' => ['max:160'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $id = $this->buyers->create($ctx->farmId(), [
            'name' => trim((string) $d['name']),
            'type' => (string) $d['type'],
            'phone' => ($d['phone'] ?? '') !== '' ? trim((string) $d['phone']) : null,
            'email' => ($d['email'] ?? '') !== '' ? trim((string) $d['email']) : null,
            'location' => ($d['location'] ?? '') !== '' ? trim((string) $d['location']) : null,
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ]);
        AuditLog::user('api.buyer.created', (string) ApiAuth::id(), ['buyer_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->buyers->find($ctx->farmId(), $id)], 201);
    }

    public function destroyBuyer(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->buyers->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Buyer not found.'], 404);
        }
        $this->buyers->delete($ctx->farmId(), $id);
        AuditLog::user('api.buyer.deleted', (string) ApiAuth::id(), ['buyer_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    public function offers(Request $request): Response
    {
        return Response::json(['data' => $this->offers->forFarm(FarmContext::current()->farmId())]);
    }

    public function storeOffer(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $v = Validator::make($request->all(), [
            'buyer_id' => [],
            'crop_name' => ['required', 'max:120'],
            'quantity_wanted' => ['numeric', 'min:0', 'max:100000000'],
            'unit' => ['max:20'],
            'price_offered' => ['numeric', 'min:0', 'max:100000000'],
            'expiry_date' => ['date'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $buyerId = (string) ($d['buyer_id'] ?? '');
        if ($buyerId !== '' && $this->buyers->find($ctx->farmId(), $buyerId) === null) {
            $buyerId = '';
        }

        $id = $this->offers->create($ctx->farmId(), [
            'buyer_id' => $buyerId ?: null,
            'crop_name' => trim((string) $d['crop_name']),
            'quantity_wanted' => ($d['quantity_wanted'] ?? '') !== '' ? (float) $d['quantity_wanted'] : null,
            'unit' => ($d['unit'] ?? '') !== '' ? trim((string) $d['unit']) : 'kg',
            'price_offered' => ($d['price_offered'] ?? '') !== '' ? (float) $d['price_offered'] : null,
            'expiry_date' => ($d['expiry_date'] ?? '') !== '' ? date('Y-m-d', (int) strtotime((string) $d['expiry_date'])) : null,
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ]);
        AuditLog::user('api.buyer_offer.created', (string) ApiAuth::id(), ['offer_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->offers->find($ctx->farmId(), $id)], 201);
    }

    /** Status enum checked manually, matching the web controller — not a Validator rule. */
    public function updateOfferStatus(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->offers->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Offer not found.'], 404);
        }
        $status = (string) $request->input('status', '');
        if (!in_array($status, self::OFFER_STATUSES, true)) {
            return Response::json(['error' => 'Invalid status.'], 422);
        }
        $this->offers->updateStatus($ctx->farmId(), $id, $status);
        AuditLog::user('api.buyer_offer.status_updated', (string) ApiAuth::id(), ['offer_id' => $id, 'status' => $status], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->offers->find($ctx->farmId(), $id)]);
    }

    public function destroyOffer(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->offers->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Offer not found.'], 404);
        }
        $this->offers->delete($ctx->farmId(), $id);
        AuditLog::user('api.buyer_offer.deleted', (string) ApiAuth::id(), ['offer_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }
}
