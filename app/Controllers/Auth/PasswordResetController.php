<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuthTokenRepository;
use App\Repositories\UserRepository;

/**
 * Forgot-password flow. Request and reset responses are uniform whether or not
 * the email is on file, so the endpoint cannot be used to enumerate accounts.
 */
final class PasswordResetController extends Controller
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuthTokenRepository $tokens = new AuthTokenRepository(),
    ) {
    }

    public function showRequest(Request $request): Response
    {
        return $this->view('auth/forgot', ['title' => 'Reset your password']);
    }

    public function sendLink(Request $request): Response
    {
        $data = $this->validate($request, [
            'email' => ['required', 'email', 'max:190'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $email = mb_strtolower((string) $data['email']);
        $user = $this->users->findByEmail($email);

        if ($user !== null && (int) $user['is_active'] === 1) {
            $token = $this->tokens->issue((string) $user['id'], 'reset', (int) Config::get('security.token_ttl', 3600));
            Mailer::queue($email, (string) ($user['name'] ?? ''), 'Reset your AgriVault password', 'reset', [
                'name' => (string) ($user['name'] ?? 'there'),
                'link' => url('reset-password?token=' . urlencode($token)),
                'ttlMinutes' => (int) round(((int) Config::get('security.token_ttl', 3600)) / 60),
            ]);
            AuditLog::user('auth.reset_requested', (string) $user['id'], [], null, $request->ip());
        } else {
            AuditLog::record('auth.reset_requested_unknown', 'system', null, null, null, null, ['email' => $email], $request->ip());
        }

        return $this->view('auth/forgot-sent', ['title' => 'Check your email', 'email' => $email]);
    }

    public function showReset(Request $request): Response
    {
        $token = (string) $request->query('token', '');
        return $this->view('auth/reset', [
            'title' => 'Choose a new password',
            'token' => $token,
        ]);
    }

    public function reset(Request $request): Response
    {
        $data = $this->validate($request, [
            'token'    => ['required'],
            'password' => ['required', 'min:10', 'max:200', 'confirmed'],
        ], [
            'password.min' => 'Use at least 10 characters.',
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $token = (string) $data['token'];
        $userId = $this->tokens->resolve($token, 'reset');

        if ($userId === null) {
            Flash::error('This reset link is invalid or has expired. Please request a new one.');
            return $this->redirect(url('forgot-password'));
        }

        $this->users->setPassword($userId, Auth::hash((string) $data['password']));
        $this->tokens->consume($token, 'reset');

        // Invalidate every existing session for this account.
        \App\Core\Database::instance()->delete('sessions', ['user_id' => $userId]);

        AuditLog::user('auth.password_reset', $userId, [], null, $request->ip());

        Flash::success('Your password has been changed. Please sign in.');
        return $this->redirect(url('login'));
    }
}
