<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Small regex router with a middleware pipeline.
 *
 * Route patterns use `{param}` placeholders (matched as [^/]+) and optional
 * `{param:\d+}` custom constraints. Handlers are [ControllerClass, 'method']
 * or a Closure. Middleware are class-strings implementing Middleware, with an
 * optional `:arg` suffix passed to the constructor.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,params:list<string>,handler:mixed,middleware:list<string>}> */
    private array $routes = [];

    /** @var list<string> */
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    /** @param list<string> $middleware */
    public function group(string $prefix, array $middleware, Closure $registrar): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMw = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . $prefix;
        $this->groupMiddleware = [...$previousMw, ...$middleware];

        $registrar($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMw;
    }

    /** @param list<string> $middleware */
    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function patch(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    private function add(string $method, string $path, mixed $handler, array $middleware): void
    {
        $full = $this->groupPrefix . $path;
        $full = '/' . trim($full, '/');
        if ($full === '/') {
            $full = '/';
        }

        $params = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                $constraint = $m[2] ?? '[^/]+';
                return '(' . $constraint . ')';
            },
            $full,
        );

        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => [...$this->groupMiddleware, ...$middleware],
        ];
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }
            $pathMatched = true;

            if ($route['method'] !== $request->method) {
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['params'], array_map('rawurldecode', $matches)) ?: [];
            $request = $request->withRouteParams($params);

            $core = function (Request $req) use ($route): Response {
                return $this->invoke($route['handler'], $req);
            };

            return $this->pipeline($route['middleware'], $core)($request);
        }

        if ($pathMatched) {
            return Response::view('errors/405', [], 405);
        }
        return Response::view('errors/404', [], 404);
    }

    /**
     * @param list<string> $middleware
     */
    private function pipeline(array $middleware, Closure $core): Closure
    {
        return array_reduce(
            array_reverse($middleware),
            static function (Closure $next, string $spec): Closure {
                return static function (Request $request) use ($next, $spec): Response {
                    [$class, $arg] = array_pad(explode(':', $spec, 2), 2, null);

                    $fqcn = str_contains($class, '\\') ? $class : 'App\\Middleware\\' . $class;
                    if (!class_exists($fqcn)) {
                        throw new RuntimeException("Unknown middleware: {$spec}");
                    }

                    /** @var Middleware $instance */
                    $instance = $arg === null ? new $fqcn() : new $fqcn($arg);
                    return $instance->handle($request, $next);
                };
            },
            $core,
        );
    }

    private function invoke(mixed $handler, Request $request): Response
    {
        if ($handler instanceof Closure) {
            return $this->coerce($handler($request));
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (!class_exists($class)) {
                throw new RuntimeException("Controller not found: {$class}");
            }
            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Action not found: {$class}::{$method}");
            }
            return $this->coerce($controller->{$method}($request));
        }

        throw new RuntimeException('Invalid route handler.');
    }

    private function coerce(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_string($result)) {
            return Response::html($result);
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        throw new RuntimeException('Route handler must return a Response, string, or array.');
    }
}
