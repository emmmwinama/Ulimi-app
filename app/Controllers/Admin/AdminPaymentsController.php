<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PaymentRepository;
use App\Support\Dates;

final class AdminPaymentsController extends Controller
{
    private const PAYMENT_METHODS = ['cash', 'mobile_money', 'bank_transfer', 'card', 'paypal'];

    public function __construct(
        private readonly PaymentRepository $payments = new PaymentRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $db = Database::instance();
        $rows = $this->payments->allWithUser();

        $users = $db->select(
            "SELECT u.id, u.name, u.email FROM users u
             JOIN subscriptions s ON s.user_id = u.id
             ORDER BY u.name ASC",
        );

        return $this->view('admin/payments/index', [
            'title'    => 'Payments',
            'active'   => 'payments',
            'rows'     => $rows,
            'users'    => $users,
            'methods'  => self::PAYMENT_METHODS,
            'total'    => $this->payments->totalCollected(),
            'thisMonth'=> $this->payments->totalCollectedThisMonth(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'user_id'   => ['required', 'max:40'],
            'amount'    => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'method'    => ['required', 'in:' . implode(',', self::PAYMENT_METHODS)],
            'reference' => ['max:120'],
            'notes'     => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $db = Database::instance();
        $subscriptionId = $db->scalar(
            'SELECT id FROM subscriptions WHERE user_id = :uid LIMIT 1',
            ['uid' => $data['user_id']],
        );
        if ($subscriptionId === false) {
            return $this->fieldError($request, 'user_id', 'That user has no subscription to bill.');
        }

        $this->payments->create([
            'subscription_id' => (string) $subscriptionId,
            'amount'    => (float) $data['amount'],
            'currency'  => 'MWK',
            'status'    => 'paid',
            'method'    => (string) $data['method'],
            'reference' => ($data['reference'] ?? '') !== '' ? trim((string) $data['reference']) : null,
            'notes'     => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
            'paid_at'   => Dates::nowUtc(),
        ], (string) AdminAuth::id());

        AuditLog::admin('admin.payment_recorded', (string) AdminAuth::id(), 'subscription', (string) $subscriptionId, ['amount' => $data['amount']], $request->ip());
        Flash::success('Payment recorded.');
        return $this->redirect(url('admin/payments'));
    }
}