<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AdminUserRepository;

final class AdminUsersController extends Controller
{
    public function __construct(private readonly AdminUserRepository $users = new AdminUserRepository())
    {
    }

    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');
        return $this->view('admin/users/index', [
            'title'  => 'Users',
            'users'  => $this->users->all($q),
            'q'      => $q,
            'counts' => $this->users->counts(),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (string) $request->route('id');
        $user = $this->users->find($id);
        if ($user === null) {
            Flash::error('User not found.');
            return $this->redirect(url('admin/users'));
        }
        return $this->view('admin/users/show', [
            'title' => (string) ($user['name'] ?: $user['email']),
            'user'  => $user,
            'farms' => $this->users->farmsFor($id),
        ]);
    }

    public function toggleActive(Request $request): Response
    {
        $id = (string) $request->route('id');
        $user = $this->users->find($id);
        if ($user === null) {
            Flash::error('User not found.');
            return $this->redirect(url('admin/users'));
        }
        $newState = (int) $user['is_active'] !== 1;
        $this->users->setActive($id, $newState);
        AuditLog::admin('admin.user_' . ($newState ? 'activated' : 'deactivated'), (string) AdminAuth::id(), 'user', $id, [], $request->ip());
        Flash::success($newState ? 'Account activated.' : 'Account deactivated.');
        return $this->redirect(url('admin/users/' . $id));
    }
}
