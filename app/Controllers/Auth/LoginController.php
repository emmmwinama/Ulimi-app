<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class LoginController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Sign in']);
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request, [
            'email'    => ['required', 'email', 'max:190'],
            'password' => ['required', 'max:200'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $limiter = RateLimiter::instance();
        $emailBucket = 'login_email:' . mb_strtolower((string) $data['email']);

        // Per-account throttle in addition to the per-IP Throttle middleware.
        if ($limiter->tooManyAttempts($emailBucket, 5)) {
            return $this->fieldError($request, 'email', 'Too many attempts for this account. Try again later.');
        }

        $user = Auth::attempt((string) $data['email'], (string) $data['password']);

        if ($user === null) {
            $limiter->hit($emailBucket, 900);
            AuditLog::record('auth.login_failed', 'system', null, 'user', null, null,
                ['email' => mb_strtolower((string) $data['email'])], $request->ip());
            // Deliberately vague; do not reveal whether the email exists.
            return $this->fieldError($request, 'email', 'Those credentials do not match our records.');
        }

        if ((int) $user['is_active'] !== 1) {
            AuditLog::record('auth.login_inactive', 'user', (string) $user['id'], null, null, null, [], $request->ip());
            return $this->fieldError(
                $request,
                'email',
                'Your account is not activated yet. Check your email for the activation link.',
            );
        }

        $limiter->clear($emailBucket);
        Auth::login($user);
        AuditLog::user('auth.login', (string) $user['id'], [], null, $request->ip());

        $intended = Session::instance()->pull('_intended');
        $target = is_string($intended) && str_starts_with($intended, '/') ? $intended : '/dashboard';

        Flash::success('Welcome back.');
        return $this->redirect(url(ltrim($target, '/')));
    }

    public function logout(Request $request): Response
    {
        $id = Auth::id();
        Auth::logout();
        if ($id !== null) {
            AuditLog::user('auth.logout', $id, [], null, $request->ip());
        }
        Flash::info('You have been signed out.');
        return $this->redirect(url('login'));
    }
}
