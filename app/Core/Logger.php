<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal file logger. One line-delimited file per day under storage/logs.
 * No PII or secrets should be passed in `context`; callers are responsible,
 * but a small redaction pass catches the obvious keys.
 */
final class Logger
{
    private static ?self $instance = null;

    private function __construct(private readonly string $dir)
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0770, true);
        }
    }

    public static function init(string $dir): self
    {
        return self::$instance ??= new self($dir);
    }

    public static function instance(): self
    {
        return self::$instance ??= new self(storage_path('logs'));
    }

    /** @param array<string,mixed> $context */
    public function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $this->interpolate($message, $context),
            $context === [] ? '' : ' ' . $this->encode($this->redact($context)),
        );

        $file = $this->dir . '/app-' . date('Y-m-d') . '.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string,mixed> $c */
    public function debug(string $m, array $c = []): void { $this->log('debug', $m, $c); }
    /** @param array<string,mixed> $c */
    public function info(string $m, array $c = []): void { $this->log('info', $m, $c); }
    /** @param array<string,mixed> $c */
    public function warning(string $m, array $c = []): void { $this->log('warning', $m, $c); }
    /** @param array<string,mixed> $c */
    public function error(string $m, array $c = []): void { $this->log('error', $m, $c); }

    /** @param array<string,mixed> $context */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replacements['{' . $key . '}'] = (string) $value;
            }
        }
        return strtr($message, $replacements);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function redact(array $context): array
    {
        $sensitive = ['password', 'pass', 'pwd', 'token', 'secret', 'authorization', 'cookie', 'key'];
        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $context[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $context[$key] = $this->redact($value);
            }
        }
        return $context;
    }

    /** @param array<mixed> $data */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
            ?: '{"log_encode_error":true}';
    }
}
