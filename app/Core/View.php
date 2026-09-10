<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Plain-PHP template renderer with a single-level layout mechanism.
 *
 * A page template calls `$this->layout('layouts/app')` and defines named
 * sections with `$this->start('name') ... $this->stop()`. The layout prints
 * them with `$this->section('name')` / `$this->yieldContent()`.
 *
 * All data passed in is available as local variables. Nothing is auto-escaped
 * by the renderer itself — templates call `e()` on every dynamic value. That is
 * a deliberate, reviewable convention: a missing `e()` is easy to spot in diff.
 */
final class View
{
    private static ?self $instance = null;

    private string $viewPath;
    /** @var array<string,string> */
    private array $sections = [];
    /** @var list<string> */
    private array $sectionStack = [];
    private ?string $layout = null;
    private string $capturedBody = '';
    /** @var array<string,mixed> */
    private array $shared = [];

    private function __construct()
    {
        $this->viewPath = rtrim((string) Config::get('paths.views', base_path('app/Views')), '/');
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string,mixed> $data */
    public function render(string $template, array $data = []): string
    {
        $this->layout = null;
        $this->sections = [];
        $this->sectionStack = [];
        $this->capturedBody = '';

        $body = $this->evaluate($this->resolve($template), array_merge($this->shared, $data));

        if ($this->layout === null) {
            return $body;
        }

        // A template may either echo its body directly (captured here) or wrap
        // it in `start('content') ... stop()`. An explicit section wins.
        $this->capturedBody = $body;
        $layout = $this->layout;
        $this->layout = null;

        return $this->evaluate($this->resolve($layout), array_merge($this->shared, $data));
    }

    /* ----------------------------------------------- template-facing helpers */

    public function layout(string $name): void
    {
        $this->layout = $name;
    }

    public function start(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function stop(): void
    {
        $name = array_pop($this->sectionStack);
        if ($name === null) {
            throw new RuntimeException('View::stop() called without a matching start().');
        }
        $this->sections[$name] = ob_get_clean() ?: '';
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function yieldContent(): string
    {
        return $this->sections['content'] ?? $this->capturedBody;
    }

    /** @param array<string,mixed> $data */
    public function partial(string $name, array $data = []): string
    {
        return $this->evaluate($this->resolve($name), array_merge($this->shared, $data));
    }

    /* --------------------------------------------------------------- internals */

    private function resolve(string $name): string
    {
        $file = $this->viewPath . '/' . str_replace(['..', '\\'], '', $name) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$name}");
        }
        return $file;
    }

    /**
     * @param array<string,mixed> $__data
     *
     * The parameter is `$__data` (not `$data`) on purpose: `extract()` runs with
     * EXTR_SKIP, so any variable that already exists in this scope would shadow a
     * template variable of the same name. A template passing `data` as a key is
     * common, so the internals stay out of that namespace.
     */
    private function evaluate(string $file, array $__data): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            (function () use ($file, $__data): void {
                extract($__data, EXTR_SKIP);
                require $file;
            })();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return ob_get_clean() ?: '';
    }
}
