<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AdminAuthController extends Controller
{
    public function show(Request $request): Response
    {
        if (AdminAuth::check()) {
            return $this->redirect(url('admin'));
        }
        return $this->view('admin/login', ['title' => 'Admin sign in']);
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
        $bucket = 'admin_login:' . mb_strtolower((string) $data['email']);
        if ($limiter->tooManyAttempts($bucket, 5)) {
            return $this->fieldError($request, 'email', 'Too many attempts. Try again later.');
        }

        $admin = AdminAuth::attempt((string) $data['email'], (string) $data['password']);
        if ($admin === null) {
            $limiter->hit($bucket, 900);
            AuditLog::record('admin.login_failed', 'system', null, null, null, null,
                ['email' => mb_strtolower((string) $data['email'])], $request->ip());
            return $this->fieldError($request, 'email', 'Those credentials do not match our records.');
        }

        $limiter->clear($bucket);
        AdminAuth::login($admin);
        AuditLog::admin('admin.login', (string) $admin['id'], null, null, [], $request->ip());

        $intended = Session::instance()->pull('_admin_intended');
        $target = is_string($intended) && str_starts_with($intended, '/admin') ? $intended : '/admin';

        Flash::success('Signed in.');
        return $this->redirect(url(ltrim($target, '/')));
    }

    public function logout(Request $request): Response
    {
        $id = AdminAuth::id();
        AdminAuth::logout();
        if ($id !== null) {
            AuditLog::admin('admin.logout', $id, null, null, [], $request->ip());
        }
        return $this->redirect(url('admin/login'));
    }
}
