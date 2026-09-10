<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SubscriptionRepository;

/**
 * Lazy subscription lifecycle. There is no cron on the target host, so status
 * transitions are computed whenever a subscription is loaded into a request
 * (login, dashboard, any farm-scoped page) and persisted once when they change.
 *
 * Transition model (matches the original's trial + grace design):
 *
 *   trial      --(trial_ends_at passed)-->            past_due (view-only grace)
 *   active     --(end_date passed)-->                 past_due (view-only grace)
 *   past_due   --(grace window passed)-->             expired
 *   suspended  -- admin only --
 *
 * "past_due" keeps read access but blocks writes (FarmContext::isReadOnly()).
 */
final class SubscriptionService
{
    /** View-only grace after the paid/trial period lapses. */
    private const GRACE_DAYS = 14;

    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {
    }

    /**
     * Return the subscription row with its status brought up to date. Writes
     * back only if the status actually changed.
     *
     * @param array<string,mixed> $subscription  row from forUserWithTier()
     * @return array<string,mixed>
     */
    public function reconcile(array $subscription): array
    {
        $status = (string) $subscription['status'];
        $now = time();

        $periodEnd = $this->timestamp(
            $status === 'trial'
                ? ($subscription['trial_ends_at'] ?? null)
                : ($subscription['end_date'] ?? null),
        );

        $newStatus = $status;

        if (in_array($status, ['trial', 'active'], true) && $periodEnd !== null && $periodEnd < $now) {
            $newStatus = 'past_due';
        }

        if ($status === 'past_due' || $newStatus === 'past_due') {
            $graceEnd = $periodEnd !== null ? $periodEnd + self::GRACE_DAYS * 86400 : null;
            if ($graceEnd !== null && $graceEnd < $now) {
                $newStatus = 'expired';
            }
        }

        if ($newStatus !== $status) {
            $this->subscriptions->updateStatus((string) $subscription['id'], $newStatus);
            $subscription['status'] = $newStatus;
        }

        return $subscription;
    }

    private function timestamp(mixed $value): ?int
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $ts = strtotime($value . ' UTC');
        return $ts === false ? null : $ts;
    }
}
