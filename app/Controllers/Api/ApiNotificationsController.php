<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificationRepository;
use App\Services\NotificationGenerator;

/** No create endpoint — notifications are system-generated, never user-created. */
final class ApiNotificationsController
{
    public function __construct(
        private readonly NotificationRepository $notifications = new NotificationRepository(),
        private readonly NotificationGenerator $generator = new NotificationGenerator(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $userId = (string) ApiAuth::id();
        $this->generator->run($userId, $ctx->farmId(), $ctx->farm);

        $limit = (int) $request->query('limit', 30);
        return Response::json(['data' => $this->notifications->forUser($userId, $limit > 0 ? $limit : 30)]);
    }

    public function unreadCount(Request $request): Response
    {
        return Response::json(['data' => ['count' => $this->notifications->unreadCount((string) ApiAuth::id())]]);
    }

    public function markRead(Request $request): Response
    {
        $this->notifications->markRead((string) ApiAuth::id(), (string) $request->route('id'));
        return Response::json(['data' => true]);
    }

    public function markAllRead(Request $request): Response
    {
        $this->notifications->markAllRead((string) ApiAuth::id());
        return Response::json(['data' => true]);
    }
}
