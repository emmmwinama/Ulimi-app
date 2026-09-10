<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuthTokenRepository;
use App\Repositories\UserRepository;

/**
 * Account activation via emailed token. One click: verify → activate → sign in.
 */
final class ActivationController extends Controller
{
    public function __construct(
        private readonly AuthTokenRepository $tokens = new AuthTokenRepository(),
        private readonly UserRepository $users = new UserRepository(),
    ) {
    }

    public function show(Request $request): Response
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            return $this->view('auth/activate-result', [
                'title' => 'Activation',
                'ok'    => false,
                'message' => 'This activation link is missing its token.',
            ]);
        }

        return $this->view('auth/activate-confirm', [
            'title' => 'Activate your account',
            'token' => $token,
        ]);
    }

    public function activate(Request $request): Response
    {
        $token = (string) $request->input('token', '');
        $userId = $token !== '' ? $this->tokens->resolve($token, 'activation') : null;

        if ($userId === null) {
            AuditLog::record('auth.activation_failed', 'system', null, null, null, null, [], $request->ip());
            return $this->view('auth/activate-result', [
                'title'   => 'Activation',
                'ok'      => false,
                'message' => 'This activation link is invalid or has expired. Request a new one by signing in.',
            ]);
        }

        $user = $this->users->find($userId);
        if ($user === null) {
            return $this->view('auth/activate-result', [
                'title' => 'Activation', 'ok' => false,
                'message' => 'We could not find that account.',
            ]);
        }

        if ((int) $user['is_active'] !== 1) {
            Database::instance()->update('users', ['is_active' => 1, 'updated_at' => \App\Support\Dates::nowUtc()], ['id' => $userId]);
        }
        $this->tokens->consume($token, 'activation');

        AuditLog::user('auth.activated', $userId, [], null, $request->ip());

        $user['is_active'] = 1;
        Auth::login($user);
        Flash::success('Your account is active. Let’s set up your farm.');

        return $this->redirect(url('onboarding/farm'));
    }
}
