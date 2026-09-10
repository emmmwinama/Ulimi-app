<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuthTokenRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\UserRepository;

/**
 * Self-service signup. Creates an inactive account + a trial subscription and
 * emails an activation link. The password is chosen here; activation only
 * confirms the address.
 */
final class RegisterController extends Controller
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly AuthTokenRepository $tokens = new AuthTokenRepository(),
    ) {
    }

    public function show(Request $request): Response
    {
        return $this->view('auth/register', [
            'title' => 'Create your account',
            'tiers' => $this->subscriptions->publicTiers(),
        ]);
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'     => ['required', 'max:120'],
            'email'    => ['required', 'email', 'max:190'],
            'password' => ['required', 'min:10', 'max:200', 'confirmed'],
            'tier_id'  => ['required', 'max:40'],
            'terms'    => ['required'],
        ], [
            'password.min'   => 'Use at least 10 characters for your password.',
            'terms.required' => 'Please accept the terms to continue.',
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $email = mb_strtolower((string) $data['email']);

        if (strcasecmp((string) $data['password'], $email) === 0
            || stripos((string) $data['password'], (string) $data['name']) !== false && mb_strlen((string) $data['name']) > 4) {
            return $this->fieldError($request, 'password', 'Choose a password that is not based on your name or email.');
        }

        $tier = $this->subscriptions->defaultTier();
        $selected = Database::instance()->selectOne(
            'SELECT * FROM subscription_tiers WHERE id = :id AND is_active = 1 AND is_public = 1 LIMIT 1',
            ['id' => $data['tier_id']],
        );
        $tier = $selected ?? $tier;
        if ($tier === null) {
            Flash::error('Sign-up is temporarily unavailable. Please try again shortly.');
            return $this->back($request, '/register');
        }

        // Enumeration-safe: identical UX whether or not the email already exists.
        if ($this->users->emailExists($email)) {
            AuditLog::record('auth.register_duplicate', 'system', null, 'user', null, null, ['email' => $email], $request->ip());
            return $this->view('auth/verify-sent', [
                'title' => 'Confirm your email',
                'email' => $email,
            ]);
        }

        $userId = Database::instance()->transaction(function () use ($data, $email, $tier): string {
            $uid = $this->users->create([
                'name'          => trim((string) $data['name']),
                'email'         => $email,
                'password_hash' => Auth::hash((string) $data['password']),
                'is_active'     => 0,
            ]);

            $this->subscriptions->create([
                'user_id'       => $uid,
                'tier_id'       => (string) $tier['id'],
                'status'        => 'trial',
                'billing_cycle' => 'monthly',
                'trial_ends_at' => date('Y-m-d H:i:s', time() + 7 * 86400),
            ]);

            return $uid;
        });

        $token = $this->tokens->issue($userId, 'activation', (int) Config::get('security.token_ttl', 3600) * 24);

        Mailer::queue($email, trim((string) $data['name']), 'Activate your AgriVault account', 'activate', [
            'name' => trim((string) $data['name']),
            'link' => url('activate?token=' . urlencode($token)),
        ]);

        AuditLog::record('auth.register', 'user', $userId, null, null, null, ['tier' => $tier['name']], $request->ip());

        return $this->view('auth/verify-sent', [
            'title' => 'Confirm your email',
            'email' => $email,
        ]);
    }
}
