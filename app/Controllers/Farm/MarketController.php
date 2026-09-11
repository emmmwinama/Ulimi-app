<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\MarketPriceRepository;

final class MarketController extends Controller
{
    public function __construct(private readonly MarketPriceRepository $prices = new MarketPriceRepository())
    {
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
}
