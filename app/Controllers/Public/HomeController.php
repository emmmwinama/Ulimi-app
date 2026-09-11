<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CmsRepository;
use App\Repositories\SubscriptionRepository;

final class HomeController extends Controller
{
    public function __construct(
        private readonly CmsRepository $cms = new CmsRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect(url('dashboard'));
        }

        $content = $this->cms->allContent();

        return $this->view('pages/home', [
            'title'        => ($content['hero_title'] ?? 'The record vault your farm can prove.') . ' — AgriVault',
            'content'      => $content,
            'features'     => $this->cms->features(true),
            'testimonials' => $this->cms->testimonials(true),
            'tiers'        => $this->subscriptions->publicTiers(),
        ]);
    }
}
