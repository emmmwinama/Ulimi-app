<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PaymentRepository;
use App\Repositories\SubscriptionRepository;

final class AdminSubscriptionsController extends Controller
{
    private const STATUSES = ['trial', 'active', 'past_due', 'expired', 'suspended'];
    private const PAYMENT_METHODS = ['cash', 'mobile_money', 'bank_transfer', 'card', 'paypal'];
    private const PAYMENT_STATUSES = ['paid', 'pending', 'failed', 'refunded'];

    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly PaymentRepository $payments = new PaymentRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        return $this->view('admin/subscriptions/index', [
            'title'    => 'Subscriptions',
            'active'   => 'subscriptions',
            'rows'     => $this->subscriptions->allWithUser($status),
            'status'   => $status,
            'statuses' => self::STATUSES,
            'counts'   => $this->subscriptions->counts(),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (string) $request->route('id');
        $sub = $this->subscriptions->find($id);
        if ($sub === null) {
            Flash::error('Subscription not found.');
            return $this->redirect(url('admin/subscriptions'));
        }
        return $this->view('admin/subscriptions/show', [
            'title'    => (string) $sub['user_name'],
            'active'   => 'subscriptions',
            'sub'      => $sub,
            'tiers'    => $this->subscriptions->allTiers(),
            'payments' => $this->payments->forSubscription($id),
            'statuses' => self::STATUSES,
            'methods'  => self::PAYMENT_METHODS,
            'pstatuses'=> self::PAYMENT_STATUSES,
        ]);
    }

    public function updateStatus(Request $request): Response
    {
        $id = (string) $request->route('id');
        $data = $this->validate($request, ['status' => ['required', 'in:' . implode(',', self::STATUSES)]]);
        if ($data instanceof Response) {
            return $data;
        }
        $this->subscriptions->updateStatus($id, (string) $data['status']);
        AuditLog::admin('admin.subscription_status_changed', (string) AdminAuth::id(), 'subscription', $id, ['status' => $data['status']], $request->ip());
        Flash::success('Status updated.');
        return $this->redirect(url('admin/subscriptions/' . $id));
    }

    public function changeTier(Request $request): Response
    {
        $id = (string) $request->route('id');
        $data = $this->validate($request, ['tier_id' => ['required']]);
        if ($data instanceof Response) {
            return $data;
        }
        if ($this->subscriptions->findTier((string) $data['tier_id']) === null) {
            return $this->fieldError($request, 'tier_id', 'Choose a valid tier.');
        }
        $this->subscriptions->changeTier($id, (string) $data['tier_id']);
        AuditLog::admin('admin.subscription_tier_changed', (string) AdminAuth::id(), 'subscription', $id, ['tier_id' => $data['tier_id']], $request->ip());
        Flash::success('Plan changed.');
        return $this->redirect(url('admin/subscriptions/' . $id));
    }

    public function extend(Request $request): Response
    {
        $id = (string) $request->route('id');
        $data = $this->validate($request, ['end_date' => ['required', 'date']]);
        if ($data instanceof Response) {
            return $data;
        }
        $this->subscriptions->extendEndDate($id, date('Y-m-d', (int) strtotime((string) $data['end_date'])));
        AuditLog::admin('admin.subscription_extended', (string) AdminAuth::id(), 'subscription', $id, ['end_date' => $data['end_date']], $request->ip());
        Flash::success('Billing period updated.');
        return $this->redirect(url('admin/subscriptions/' . $id));
    }

    public function recordPayment(Request $request): Response
    {
        $id = (string) $request->route('id');
        $sub = $this->subscriptions->find($id);
        if ($sub === null) {
            Flash::error('Subscription not found.');
            return $this->redirect(url('admin/subscriptions'));
        }

        $data = $this->validate($request, [
            'amount'    => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'currency'  => ['required', 'max:3'],
            'status'    => ['required', 'in:' . implode(',', self::PAYMENT_STATUSES)],
            'method'    => ['required', 'in:' . implode(',', self::PAYMENT_METHODS)],
            'reference' => ['max:120'],
            'paid_at'   => ['date'],
            'notes'     => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $this->payments->create([
            'subscription_id' => $id,
            'amount' => (float) $data['amount'],
            'currency' => strtoupper((string) $data['currency']),
            'status' => (string) $data['status'],
            'method' => (string) $data['method'],
            'reference' => ($data['reference'] ?? '') !== '' ? trim((string) $data['reference']) : null,
            'notes' => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
            'paid_at' => ($data['paid_at'] ?? '') !== '' ? date('Y-m-d H:i:s', (int) strtotime((string) $data['paid_at'])) : null,
        ], (string) AdminAuth::id());

        AuditLog::admin('admin.payment_recorded', (string) AdminAuth::id(), 'subscription', $id, ['amount' => $data['amount']], $request->ip());
        Flash::success('Payment recorded.');
        return $this->redirect(url('admin/subscriptions/' . $id));
    }
}
