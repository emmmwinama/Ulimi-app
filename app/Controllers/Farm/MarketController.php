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
use App\Repositories\BuyerOfferRepository;
use App\Repositories\BuyerRepository;
use App\Repositories\MarketPriceRepository;

final class MarketController extends Controller
{
    private const BUYER_TYPES = ['individual', 'company', 'cooperative', 'other'];
    private const OFFER_STATUSES = ['open', 'accepted', 'declined', 'expired'];

    public function __construct(
        private readonly MarketPriceRepository $prices = new MarketPriceRepository(),
        private readonly BuyerRepository $buyers = new BuyerRepository(),
        private readonly BuyerOfferRepository $offers = new BuyerOfferRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $crop = (string) $request->query('crop', '');
        return $this->view('market/index', [
            'title'  => 'Market prices',
            'active' => 'market',
            'prices' => $this->prices->active($crop ?: null),
            'crops'  => $this->prices->crops(),
            'crop'   => $crop,
        ]);
    }

    /* ----------------------------------------------------- buyers & offers */

    public function buyers(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('market/buyers', [
            'title'      => 'Buyers & Offers',
            'active'     => 'market',
            'buyers'     => $this->buyers->forFarm($ctx->farmId()),
            'offers'     => $this->offers->forFarm($ctx->farmId()),
            'buyerTypes' => self::BUYER_TYPES,
            'statuses'   => self::OFFER_STATUSES,
            'canManage'  => $ctx->can('crops.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function storeBuyer(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'name'     => ['required', 'max:160'],
            'type'     => ['required', 'in:' . implode(',', self::BUYER_TYPES)],
            'phone'    => ['max:40'],
            'email'    => ['email', 'max:160'],
            'location' => ['max:160'],
            'notes'    => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->buyers->create($ctx->farmId(), [
            'name'     => trim((string) $data['name']),
            'type'     => (string) $data['type'],
            'phone'    => ($data['phone'] ?? '') !== '' ? trim((string) $data['phone']) : null,
            'email'    => ($data['email'] ?? '') !== '' ? trim((string) $data['email']) : null,
            'location' => ($data['location'] ?? '') !== '' ? trim((string) $data['location']) : null,
            'notes'    => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
        ]);
        AuditLog::user('buyer.created', (string) Auth::id(), ['buyer_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Buyer added.');
        return $this->redirect(url('market/buyers'));
    }

    public function destroyBuyer(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->buyers->find($ctx->farmId(), $id) === null) {
            Flash::error('Buyer not found.');
            return $this->redirect(url('market/buyers'));
        }
        $this->buyers->delete($ctx->farmId(), $id);
        AuditLog::user('buyer.deleted', (string) Auth::id(), ['buyer_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Buyer removed.');
        return $this->redirect(url('market/buyers'));
    }

    public function storeOffer(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'buyer_id'        => [],
            'crop_name'       => ['required', 'max:120'],
            'quantity_wanted' => ['numeric', 'min:0', 'max:100000000'],
            'unit'            => ['max:20'],
            'price_offered'   => ['numeric', 'min:0', 'max:100000000'],
            'expiry_date'     => ['date'],
            'notes'           => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $buyerId = (string) ($data['buyer_id'] ?? '');
        if ($buyerId !== '' && $this->buyers->find($ctx->farmId(), $buyerId) === null) {
            $buyerId = '';
        }

        $id = $this->offers->create($ctx->farmId(), [
            'buyer_id'        => $buyerId ?: null,
            'crop_name'       => trim((string) $data['crop_name']),
            'quantity_wanted' => ($data['quantity_wanted'] ?? '') !== '' ? (float) $data['quantity_wanted'] : null,
            'unit'            => ($data['unit'] ?? '') !== '' ? trim((string) $data['unit']) : 'kg',
            'price_offered'   => ($data['price_offered'] ?? '') !== '' ? (float) $data['price_offered'] : null,
            'expiry_date'     => ($data['expiry_date'] ?? '') !== '' ? date('Y-m-d', (int) strtotime((string) $data['expiry_date'])) : null,
            'notes'           => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
        ]);
        AuditLog::user('buyer_offer.created', (string) Auth::id(), ['offer_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Offer recorded.');
        return $this->redirect(url('market/buyers'));
    }

    public function updateOfferStatus(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->offers->find($ctx->farmId(), $id) === null) {
            Flash::error('Offer not found.');
            return $this->redirect(url('market/buyers'));
        }
        $status = (string) $request->input('status', '');
        if (!in_array($status, self::OFFER_STATUSES, true)) {
            Flash::error('Invalid status.');
            return $this->redirect(url('market/buyers'));
        }
        $this->offers->updateStatus($ctx->farmId(), $id, $status);
        Flash::success('Offer updated.');
        return $this->redirect(url('market/buyers'));
    }

    public function destroyOffer(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->offers->find($ctx->farmId(), $id) === null) {
            Flash::error('Offer not found.');
            return $this->redirect(url('market/buyers'));
        }
        $this->offers->delete($ctx->farmId(), $id);
        AuditLog::user('buyer_offer.deleted', (string) Auth::id(), ['offer_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Offer removed.');
        return $this->redirect(url('market/buyers'));
    }
}
