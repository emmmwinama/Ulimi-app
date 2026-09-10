<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable view over the current HTTP request. Built once from PHP superglobals
 * in the front controller; passed explicitly into controllers.
 */
final class Request
{
    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     * @param array<string,mixed> $server
     * @param array<string,mixed> $cookies
     * @param array<string,array<string,mixed>> $files  normalised $_FILES
     */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $cookies,
        private readonly array $files,
        private readonly array $routeParams = [],
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;

        $rawMethod = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        $body = $_POST;
        $contentType = strtolower((string) ($server['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        // Method spoofing for HTML forms: <input name="_method" value="PUT">
        $method = $rawMethod;
        if ($rawMethod === 'POST' && isset($body['_method'])) {
            $spoofed = strtoupper((string) $body['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        return new self(
            method: $method,
            path: $path,
            query: $_GET,
            body: $body,
            server: $server,
            cookies: $_COOKIE,
            files: self::normaliseFiles($_FILES),
        );
    }

    /** @param array<string,mixed> $params */
    public function withRouteParams(array $params): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->body,
            $this->server,
            $this->cookies,
            $this->files,
            $params,
        );
    }

    public function route(string $key, ?string $default = null): ?string
    {
        $value = $this->routeParams[$key] ?? $default;
        return $value === null ? null : (string) $value;
    }

    /** Body value, trimmed if scalar. */
    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        $value = $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /** @param list<string> $keys @return array<string,mixed> */
    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->input($key);
        }
        return $out;
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return $this->body + $this->query;
    }

    public function boolean(string $key): bool
    {
        return filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    public function bearerToken(): ?string
    {
        $header = (string) $this->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m) === 1) {
            return trim($m[1]);
        }
        return null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$key])) {
            return (string) $this->server[$key];
        }
        if ($name === 'Content-Type' && isset($this->server['CONTENT_TYPE'])) {
            return (string) $this->server['CONTENT_TYPE'];
        }
        return $default;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return isset($this->cookies[$name]) ? (string) $this->cookies[$name] : $default;
    }

    /** @return array<string,mixed>|null */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isReading(): bool
    {
        return in_array($this->method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    public function wantsJson(): bool
    {
        $accept = (string) $this->header('Accept', '');
        return str_contains($accept, 'application/json')
            || str_starts_with($this->path, '/api/');
    }

    public function isSecure(): bool
    {
        if (($this->server['HTTPS'] ?? '') !== '' && strtolower((string) $this->server['HTTPS']) !== 'off') {
            return true;
        }
        if ((int) ($this->server['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }
        if ($this->trustProxy() && strtolower((string) $this->header('X-Forwarded-Proto', '')) === 'https') {
            return true;
        }
        return false;
    }

    public function ip(): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');

        if ($this->trustProxy()) {
            $forwarded = (string) $this->header('X-Forwarded-For', '');
            if ($forwarded !== '') {
                $first = trim(explode(',', $forwarded)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP) !== false) {
                    return $first;
                }
            }
        }

        return $remote;
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    private function trustProxy(): bool
    {
        $trusted = (array) Config::get('trusted_proxies', []);
        if ($trusted === []) {
            return false;
        }
        return in_array((string) ($this->server['REMOTE_ADDR'] ?? ''), $trusted, true);
    }

    /**
     * Flatten PHP's awkward nested $_FILES shape into one entry per field.
     * Only single-file fields are supported (documents upload one at a time).
     *
     * @param array<string,mixed> $files
     * @return array<string,array<string,mixed>>
     */
    private static function normaliseFiles(array $files): array
    {
        $out = [];
        foreach ($files as $field => $info) {
            if (!is_array($info) || !isset($info['name'])) {
                continue;
            }
            if (is_array($info['name'])) {
                continue; // multi-file not used in this app
            }
            $out[$field] = [
                'name'     => (string) $info['name'],
                'type'     => (string) ($info['type'] ?? ''),
                'tmp_name' => (string) ($info['tmp_name'] ?? ''),
                'error'    => (int) ($info['error'] ?? UPLOAD_ERR_NO_FILE),
                'size'     => (int) ($info['size'] ?? 0),
            ];
        }
        return $out;
    }
}
