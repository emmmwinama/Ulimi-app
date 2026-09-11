<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\MarketPriceRepository;
use App\Support\Dates;

final class AdminMarketController extends Controller
{
    public function __construct(private readonly MarketPriceRepository $prices = new MarketPriceRepository())
    {
    }

    public function index(Request $request): Response
    {
        return $this->view('admin/market/index', ['title' => 'Market data', 'rows' => $this->prices->all()]);
    }

    public function store(Request $request): Response
    {
        $data = $this->prepared($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->prices->create($data);
        AuditLog::admin('admin.market_price_created', (string) AdminAuth::id(), 'market_price', $id, [], $request->ip());
        Flash::success('Price added.');
        return $this->redirect(url('admin/market'));
    }

    public function update(Request $request): Response
    {
        $id = (string) $request->route('id');
        if ($this->prices->find($id) === null) {
            Flash::error('Not found.');
            return $this->redirect(url('admin/market'));
        }
        $data = $this->prepared($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->prices->update($id, $data);
        AuditLog::admin('admin.market_price_updated', (string) AdminAuth::id(), 'market_price', $id, [], $request->ip());
        Flash::success('Price updated.');
        return $this->redirect(url('admin/market'));
    }

    public function destroy(Request $request): Response
    {
        $id = (string) $request->route('id');
        $this->prices->delete($id);
        AuditLog::admin('admin.market_price_deleted', (string) AdminAuth::id(), 'market_price', $id, [], $request->ip());
        Flash::success('Price removed.');
        return $this->redirect(url('admin/market'));
    }

    /** @return array<string,mixed>|Response */
    private function prepared(Request $request): array|Response
    {
        $data = $this->validate($request, [
            'crop_name' => ['required', 'max:120'],
            'variety'   => ['max:120'],
            'unit'      => ['required', 'max:20'],
            'price_min' => ['required', 'numeric', 'min:0'],
            'price_max' => ['required', 'numeric', 'min:0'],
            'price_avg' => ['required', 'numeric', 'min:0'],
            'market'    => ['required', 'max:120'],
            'region'    => ['required', 'max:120'],
            'currency'  => ['required', 'max:3'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        return [
            'crop_name' => trim((string) $data['crop_name']),
            'variety'   => ($data['variety'] ?? '') !== '' ? trim((string) $data['variety']) : null,
            'unit'      => trim((string) $data['unit']),
            'price_min' => (float) $data['price_min'],
            'price_max' => (float) $data['price_max'],
            'price_avg' => (float) $data['price_avg'],
            'market'    => trim((string) $data['market']),
            'region'    => trim((string) $data['region']),
            'currency'  => strtoupper((string) $data['currency']),
            'season'    => null,
            'recorded_at' => Dates::nowUtc(),
            'source'    => 'ADMARC',
            'is_active' => $request->boolean('is_active') ? 1 : 0,
        ];
    }
}
