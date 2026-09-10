<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A response value object. Controllers and middleware return one of these;
 * the front controller calls send() exactly once.
 */
final class Response
{
    /** @param array<string,string> $headers */
    private function __construct(
        private int $status = 200,
        private string $body = '',
        private array $headers = [],
    ) {
    }

    public static function make(string $body = '', int $status = 200): self
    {
        return new self($status, $body);
    }

    public static function html(string $html, int $status = 200): self
    {
        return (new self($status, $html))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function view(string $template, array $data = [], int $status = 200): self
    {
        return self::html(View::instance()->render($template, $data), $status);
    }

    /** @param mixed $data */
    public static function json($data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return (new self($status, $json === false ? '{}' : $json))
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return (new self($status, ''))->header('Location', $to);
    }

    public static function noContent(int $status = 204): self
    {
        return new self($status, '');
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /** @param array<string,string> $headers */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /** @return array<string,string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        echo $this->body;
    }
}
