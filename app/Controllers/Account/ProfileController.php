<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;

final class ProfileController extends Controller
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function edit(Request $request): Response
    {
        return $this->view('account/index', [
            'title'  => 'Account',
            'active' => 'account',
            'user'   => Auth::user() ?? [],
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $userId = (string) Auth::id();

        $data = $this->validate($request, [
            'name'  => ['required', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,' . $userId],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $this->users->update($userId, [
            'name'  => trim((string) $data['name']),
            'email' => mb_strtolower((string) $data['email']),
        ]);

        AuditLog::user('account.profile_updated', $userId, [], null, $request->ip());
        Flash::success('Your details have been saved.');
        return $this->redirect(url('account'));
    }

    public function updatePassword(Request $request): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return $this->redirect(url('login'));
        }

        $data = $this->validate($request, [
            'current_password' => ['required'],
            'password'         => ['required', 'min:10', 'max:200', 'confirmed'],
        ], [
            'password.min' => 'Use at least 10 characters.',
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        if (!password_verify((string) $data['current_password'], (string) $user['password'])) {
            return $this->fieldError($request, 'current_password', 'Your current password is incorrect.');
        }

        $this->users->setPassword((string) $user['id'], Auth::hash((string) $data['password']));

        // Drop all sessions for this account, then re-establish the current one.
        Database::instance()->delete('sessions', ['user_id' => (string) $user['id']]);
        \App\Core\Session::instance()->regenerate(true);
        Auth::login($user); // re-seat the current session

        AuditLog::user('account.password_changed', (string) $user['id'], [], null, $request->ip());
        Flash::success('Your password has been changed. Other devices have been signed out.');
        return $this->redirect(url('account'));
    }
}
