<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Support\Dates;
use App\Support\Token;
use App\Support\Ulid;

final class TeamController extends Controller
{
    private const ROLES = ['manager', 'agronomist', 'accountant', 'field_worker', 'viewer'];

    /* ------------------------------------------------------------- listing */

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $rows = Database::instance()->select(
            "SELECT m.*, u.name AS user_name, u.email AS user_email
             FROM farm_members m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.farm_id = :fid
             ORDER BY (m.role = 'owner') DESC, m.status ASC, u.name ASC",
            ['fid' => $ctx->farmId()],
        );

        $sub = $ctx->subscription;
        $maxMembers = $sub !== null ? (int) $sub['max_team_members'] : 1;
        $activeCount = 0;
        foreach ($rows as $r) {
            if ($r['status'] === 'active') {
                $activeCount++;
            }
        }

        return $this->view('team/index', [
            'title'       => 'Team',
            'active'      => 'team',
            'members'     => $rows,
            'roles'       => self::ROLES,
            'canManage'   => $ctx->can('team.manage'),
            'teamEnabled' => $sub === null ? false : (bool) $sub['team_accounts'],
            'maxMembers'  => $maxMembers,
            'activeCount' => $activeCount,
            'atLimit'     => $maxMembers !== -1 && $activeCount >= $maxMembers,
        ]);
    }

    /* -------------------------------------------------------------- invite */

    public function invite(Request $request): Response
    {
        $ctx = FarmContext::current();

        if ($ctx->subscription === null || !(bool) $ctx->subscription['team_accounts']) {
            Flash::error('Team accounts are not included in your current plan.');
            return $this->redirect(url('team'));
        }

        $data = $this->validate($request, [
            'email' => ['required', 'email', 'max:190'],
            'role'  => ['required', 'in:' . implode(',', self::ROLES)],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $email = mb_strtolower((string) $data['email']);
        $db = Database::instance();

        // Already a member (active or invited) of this farm?
        $dupe = $db->selectOne(
            "SELECT m.id FROM farm_members m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.farm_id = :fid AND (LOWER(u.email) = :email OR LOWER(m.invite_email) = :email)
             LIMIT 1",
            ['fid' => $ctx->farmId(), 'email' => $email],
        );
        if ($dupe !== null) {
            return $this->fieldError($request, 'email', 'That person is already on this farm’s team.');
        }

        $maxMembers = (int) $ctx->subscription['max_team_members'];
        $activeCount = (int) $db->scalar(
            "SELECT COUNT(*) FROM farm_members WHERE farm_id = :fid AND status = 'active'",
            ['fid' => $ctx->farmId()],
        );
        if ($maxMembers !== -1 && $activeCount >= $maxMembers) {
            Flash::error('You have reached the team-member limit for your plan.');
            return $this->redirect(url('team'));
        }

        $plain = Token::create()['plain'];
        $ttl = (int) Config::get('security.invite_ttl', 259200);

        $db->insert('farm_members', [
            'id'                => Ulid::generate(),
            'farm_id'           => $ctx->farmId(),
            'user_id'           => null,
            'role'              => (string) $data['role'],
            'permissions'       => json_encode(new \stdClass()),
            'invite_email'      => $email,
            'invite_token'      => Token::hash($plain),
            'invite_expires_at' => date(Dates::DB_FORMAT, time() + $ttl),
            'status'            => 'invited',
            'invited_by'        => (string) Auth::id(),
            'created_at'        => Dates::nowUtc(),
        ]);

        Mailer::queue($email, '', 'You have been invited to ' . $ctx->farmName() . ' on AgriVault', 'invite', [
            'farmName'   => $ctx->farmName(),
            'role'       => str_replace('_', ' ', (string) $data['role']),
            'link'       => url('invite?token=' . urlencode($plain)),
            'ttlHours'   => (int) round($ttl / 3600),
        ]);

        AuditLog::user('team.invited', (string) Auth::id(), ['email' => $email, 'role' => $data['role']], $ctx->farmId(), $request->ip());
        Flash::success('Invitation sent to ' . $email . '.');
        return $this->redirect(url('team'));
    }

    /* ------------------------------------------------------- accept invite */

    public function showAccept(Request $request): Response
    {
        $token = (string) $request->query('token', '');
        $invite = $this->resolveInvite($token);

        if ($invite === null) {
            return $this->view('team/accept', [
                'title'  => 'Invitation',
                'invite' => null,
            ]);
        }

        // Stash for a post-login/registration continuation.
        Session::instance()->put('_invite_token', $token);

        $farm = Database::instance()->selectOne('SELECT name FROM farms WHERE id = :id', ['id' => $invite['farm_id']]);

        return $this->view('team/accept', [
            'title'      => 'Invitation',
            'invite'     => $invite,
            'farmName'   => $farm['name'] ?? 'a farm',
            'authed'     => Auth::check(),
            'token'      => $token,
        ]);
    }

    public function accept(Request $request): Response
    {
        $token = (string) ($request->input('token') ?: Session::instance()->get('_invite_token', ''));
        $invite = $this->resolveInvite($token);

        if ($invite === null) {
            Flash::error('This invitation is invalid or has expired.');
            return $this->redirect(url('login'));
        }

        if (!Auth::check()) {
            Session::instance()->put('_invite_token', $token);
            Session::instance()->put('_intended', '/invite?token=' . urlencode($token));
            Flash::info('Sign in or create your account to accept this invitation.');
            return $this->redirect(url('login'));
        }

        $user = Auth::user();
        if ($user === null || strcasecmp((string) $user['email'], (string) $invite['invite_email']) !== 0) {
            Flash::error('This invitation was sent to a different email address. Sign in with that address to accept it.');
            return $this->redirect(url('dashboard'));
        }

        Database::instance()->update('farm_members', [
            'user_id'           => (string) $user['id'],
            'status'            => 'active',
            'invite_token'      => null,
            'invite_expires_at' => null,
        ], ['id' => (string) $invite['id']]);

        Session::instance()->forget('_invite_token');
        Session::instance()->put('farm_id', (string) $invite['farm_id']);

        AuditLog::user('team.invite_accepted', (string) $user['id'], ['member_id' => $invite['id']], (string) $invite['farm_id'], $request->ip());
        Flash::success('You have joined the team.');
        return $this->redirect(url('dashboard'));
    }

    /* -------------------------------------------------------- role / remove */

    public function updateRole(Request $request): Response
    {
        $ctx = FarmContext::current();
        $memberId = (string) $request->route('memberId', '');

        $data = $this->validate($request, [
            'role' => ['required', 'in:' . implode(',', self::ROLES)],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $member = $this->farmMember($ctx->farmId(), $memberId);
        if ($member === null) {
            Flash::error('Team member not found.');
            return $this->redirect(url('team'));
        }
        if ($member['role'] === 'owner') {
            Flash::error('The owner’s role cannot be changed.');
            return $this->redirect(url('team'));
        }
        if ($member['user_id'] === Auth::id()) {
            Flash::error('You cannot change your own role.');
            return $this->redirect(url('team'));
        }

        Database::instance()->update('farm_members', ['role' => (string) $data['role']], ['id' => $memberId]);
        AuditLog::user('team.role_changed', (string) Auth::id(), ['member_id' => $memberId, 'role' => $data['role']], $ctx->farmId(), $request->ip());
        Flash::success('Role updated.');
        return $this->redirect(url('team'));
    }

    public function remove(Request $request): Response
    {
        $ctx = FarmContext::current();
        $memberId = (string) $request->route('memberId', '');

        $member = $this->farmMember($ctx->farmId(), $memberId);
        if ($member === null) {
            Flash::error('Team member not found.');
            return $this->redirect(url('team'));
        }
        if ($member['role'] === 'owner') {
            Flash::error('The owner cannot be removed.');
            return $this->redirect(url('team'));
        }
        if ($member['user_id'] === Auth::id()) {
            Flash::error('You cannot remove yourself here.');
            return $this->redirect(url('team'));
        }

        Database::instance()->delete('farm_members', ['id' => $memberId]);
        AuditLog::user('team.member_removed', (string) Auth::id(), ['member_id' => $memberId], $ctx->farmId(), $request->ip());
        Flash::success('Team member removed.');
        return $this->redirect(url('team'));
    }

    /* ------------------------------------------------------------ helpers */

    /** @return array<string,mixed>|null */
    private function resolveInvite(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $row = Database::instance()->selectOne(
            "SELECT * FROM farm_members
             WHERE invite_token = :hash AND status = 'invited'
             LIMIT 1",
            ['hash' => Token::hash($token)],
        );
        if ($row === null) {
            return null;
        }
        if ($row['invite_expires_at'] !== null && strtotime((string) $row['invite_expires_at'] . ' UTC') < time()) {
            return null;
        }
        return $row;
    }

    /** @return array<string,mixed>|null */
    private function farmMember(string $farmId, string $memberId): ?array
    {
        return Database::instance()->selectOne(
            'SELECT * FROM farm_members WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $memberId, 'fid' => $farmId],
        );
    }
}
