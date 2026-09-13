<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\Config;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Support\Dates;
use App\Support\Token;
use App\Support\Ulid;

/**
 * No dedicated repository exists for team members on the web side either —
 * TeamController talks to farm_members directly. Mirrored here rather than
 * inventing a repository class the web side doesn't have.
 */
final class ApiTeamController
{
    private const ROLES = ['manager', 'agronomist', 'accountant', 'field_worker', 'viewer'];

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('team.view')) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $rows = Database::instance()->select(
            "SELECT m.*, u.name AS user_name, u.email AS user_email
             FROM farm_members m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.farm_id = :fid
             ORDER BY (m.role = 'owner') DESC, m.status ASC, u.name ASC",
            ['fid' => $ctx->farmId()],
        );
        return Response::json(['data' => $rows]);
    }

    public function invite(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('team.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        if ($ctx->subscription === null || !(bool) $ctx->subscription['team_accounts']) {
            return Response::json(['error' => 'Team accounts are not included in your current plan.'], 403);
        }

        $v = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', 'in:' . implode(',', self::ROLES)],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $email = mb_strtolower((string) $d['email']);
        $db = Database::instance();

        $dupe = $db->selectOne(
            "SELECT m.id FROM farm_members m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.farm_id = :fid AND (LOWER(u.email) = :email OR LOWER(m.invite_email) = :email)
             LIMIT 1",
            ['fid' => $ctx->farmId(), 'email' => $email],
        );
        if ($dupe !== null) {
            return Response::json(['errors' => ['email' => ['That person is already on this farm’s team.']]], 422);
        }

        $maxMembers = (int) $ctx->subscription['max_team_members'];
        $activeCount = (int) $db->scalar(
            "SELECT COUNT(*) FROM farm_members WHERE farm_id = :fid AND status = 'active'",
            ['fid' => $ctx->farmId()],
        );
        if ($maxMembers !== -1 && $activeCount >= $maxMembers) {
            return Response::json(['error' => 'You have reached the team-member limit for your plan.'], 403);
        }

        $plain = Token::create()['plain'];
        $ttl = (int) Config::get('security.invite_ttl', 259200);

        $memberId = Ulid::generate();
        $db->insert('farm_members', [
            'id' => $memberId,
            'farm_id' => $ctx->farmId(),
            'user_id' => null,
            'role' => (string) $d['role'],
            'permissions' => json_encode(new \stdClass()),
            'invite_email' => $email,
            'invite_token' => Token::hash($plain),
            'invite_expires_at' => date(Dates::DB_FORMAT, time() + $ttl),
            'status' => 'invited',
            'invited_by' => (string) ApiAuth::id(),
            'created_at' => Dates::nowUtc(),
        ]);

        Mailer::queue($email, '', 'You have been invited to ' . $ctx->farmName() . ' on AgriVault', 'invite', [
            'farmName' => $ctx->farmName(),
            'role' => str_replace('_', ' ', (string) $d['role']),
            'link' => url('invite?token=' . urlencode($plain)),
            'ttlHours' => (int) round($ttl / 3600),
        ]);

        AuditLog::user('api.team.invited', (string) ApiAuth::id(), ['email' => $email, 'role' => $d['role']], $ctx->farmId(), $request->ip());
        return Response::json(['data' => ['id' => $memberId]], 201);
    }

    public function updateRole(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('team.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $memberId = (string) $request->route('memberId', '');
        $v = Validator::make($request->all(), [
            'role' => ['required', 'in:' . implode(',', self::ROLES)],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $member = $this->farmMember($ctx->farmId(), $memberId);
        if ($member === null) {
            return Response::json(['error' => 'Team member not found.'], 404);
        }
        if ($member['role'] === 'owner') {
            return Response::json(['error' => 'The owner’s role cannot be changed.'], 422);
        }
        if ($member['user_id'] === ApiAuth::id()) {
            return Response::json(['error' => 'You cannot change your own role.'], 422);
        }

        Database::instance()->update('farm_members', ['role' => (string) $d['role']], ['id' => $memberId]);
        AuditLog::user('api.team.role_changed', (string) ApiAuth::id(), ['member_id' => $memberId, 'role' => $d['role']], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    public function remove(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('team.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $memberId = (string) $request->route('memberId', '');
        $member = $this->farmMember($ctx->farmId(), $memberId);
        if ($member === null) {
            return Response::json(['error' => 'Team member not found.'], 404);
        }
        if ($member['role'] === 'owner') {
            return Response::json(['error' => 'The owner cannot be removed.'], 422);
        }
        if ($member['user_id'] === ApiAuth::id()) {
            return Response::json(['error' => 'You cannot remove yourself here.'], 422);
        }

        Database::instance()->delete('farm_members', ['id' => $memberId]);
        AuditLog::user('api.team.member_removed', (string) ApiAuth::id(), ['member_id' => $memberId], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
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
