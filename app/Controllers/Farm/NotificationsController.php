<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificationRepository;
use App\Services\NotificationGenerator;

final class NotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationRepository $notifications = new NotificationRepository(),
        private readonly NotificationGenerator $generator = new NotificationGenerator(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $userId = (string) Auth::id();
        $this->generator->run($userId, $ctx->farmId(), $ctx->farm);

        return $this->view('notifications/index', [
            'title'  => 'Notifications',
            'active' => 'notifications',
            'items'  => $this->notifications->forUser($userId),
        ]);
    }

    public function markRead(Request $request): Response
    {
        $this->notifications->markRead((string) Auth::id(), (string) $request->route('id'));
        return $this->redirectBack($request);
    }

    public function markAllRead(Request $request): Response
    {
        $this->notifications->markAllRead((string) Auth::id());
        Flash::success('All caught up.');
        return $this->redirectBack($request);
    }

    private function redirectBack(Request $request): Response
    {
        $link = (string) $request->input('link', '');
        if ($link !== '' && str_starts_with($link, '/')) {
            return $this->redirect(url(ltrim($link, '/')));
        }
        return $this->back($request, 'notifications');
    }
}
