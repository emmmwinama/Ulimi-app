<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Jwt;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;

/**
 * Mobile JWT auth. Access tokens are short-lived signed JWTs (Core\Jwt,
 * carried in Authorization: Bearer); refresh tokens are long-lived opaque
 * strings stored hashed (Repositories\ApiTokenRepository), exchanged for a
 * new access token at /api/mobile/refresh.
 */
final class ApiAuthController
{
    private const ACCESS_TTL = 2 * 3600;
    private const REFRESH_TTL = 30 * 86400;

    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly ApiTokenRepository $tokens = new ApiTokenRepository(),
    ) {
    }

    public function login(Request $request): Response
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        if ($email === '' || $password === '') {
            return Response::json(['error' => 'Email and password are required.'], 422);
        }

        $limiter = RateLimiter::instance();
        $bucket = 'api_login:' . $request->ip();
        if ($limiter->tooManyAttempts($bucket, 8)) {
            return Response::json(['error' => 'Too many attempts. Try again later.'], 429);
        }

        $user = Auth::attempt($email, $password);
        if ($user === null) {
            $limiter->hit($bucket, 900);
            return Response::json(['error' => 'Invalid credentials.'], 401);
        }
        if ((int) $user['is_active'] !== 1) {
            return Response::json(['error' => 'Account is not activated.'], 403);
        }
        $limiter->clear($bucket);

        AuditLog::user('api.login', (string) $user['id'], [], null, $request->ip());

        return $this->tokenPair($user, (string) $request->input('device', ''));
    }

    public function refresh(Request $request): Response
    {
        $refreshToken = (string) $request->input('refresh_token', '');
        if ($refreshToken === '') {
            return Response::json(['error' => 'refresh_token is required.'], 422);
        }

        $userId = $this->tokens->resolve($refreshToken);
        if ($userId === null) {
            return Response::json(['error' => 'Invalid or expired refresh token.'], 401);
        }

        $user = $this->users->find($userId);
        if ($user === null || (int) $user['is_active'] !== 1) {
            return Response::json(['error' => 'Account unavailable.'], 403);
        }

        // Rotate: the old refresh token is single-use.
        $this->tokens->revoke($refreshToken);

        return $this->tokenPair($user, '');
    }

    public function logout(Request $request): Response
    {
        $refreshToken = (string) $request->input('refresh_token', '');
        if ($refreshToken !== '') {
            $this->tokens->revoke($refreshToken);
        }
        if (ApiAuth::id() !== null) {
            AuditLog::user('api.logout', (string) ApiAuth::id(), [], null, $request->ip());
        }
        return Response::json(['ok' => true]);
    }

    /** @param array<string,mixed> $user */
    private function tokenPair(array $user, string $device): Response
    {
        $now = time();
        $access = Jwt::encode([
            'sub' => (string) $user['id'],
            'type' => 'access',
            'iat' => $now,
            'exp' => $now + self::ACCESS_TTL,
        ]);
        $refresh = $this->tokens->issue((string) $user['id'], self::REFRESH_TTL, $device);

        return Response::json([
            'access_token' => $access,
            'refresh_token' => $refresh,
            'expires_in' => self::ACCESS_TTL,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ],
        ]);
    }
}
