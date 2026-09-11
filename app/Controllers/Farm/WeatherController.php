<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Services\Weather;

final class WeatherController extends Controller
{
    public function __construct(private readonly Weather $weather = new Weather())
    {
    }

    public function show(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('weather/index', [
            'title'   => 'Weather',
            'active'  => 'weather',
            'weather' => $this->weather->forFarm($ctx->farm),
            'farm'    => $ctx->farm,
        ]);
    }
}
