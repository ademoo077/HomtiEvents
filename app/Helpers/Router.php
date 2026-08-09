<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Routeur MVC léger.
 */
final class Router
{
    /** @var array<int, array<string, mixed>> */
    public static array $routes = [];

    private array $currentMiddleware = [];
    private string $currentPrefix = '';
    private string $currentName = '';

    public function middleware(array|string $middleware): self
    {
        $this->currentMiddleware = (array) $middleware;

        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->currentPrefix = '/' . trim($prefix, '/');

        return $this;
    }

    public function get(string $path, string|callable $handler, array $middleware = [], ?string $name = null): RouteBuilder
    {
        return $this->register('GET', $path, $handler, $middleware, $name);
    }

    public function post(string $path, string|callable $handler, array $middleware = [], ?string $name = null): RouteBuilder
    {
        return $this->register('POST', $path, $handler, $middleware, $name);
    }

    public function put(string $path, string|callable $handler, array $middleware = [], ?string $name = null): RouteBuilder
    {
        return $this->register('PUT', $path, $handler, $middleware, $name);
    }

    public function patch(string $path, string|callable $handler, array $middleware = [], ?string $name = null): RouteBuilder
    {
        return $this->register('PATCH', $path, $handler, $middleware, $name);
    }

    public function delete(string $path, string|callable $handler, array $middleware = [], ?string $name = null): RouteBuilder
    {
        return $this->register('DELETE', $path, $handler, $middleware, $name);
    }

    private function register(string $method, string $path, string|callable $handler, array $middleware = [], ?string $name = null): RouteBuilder
    {
        $fullPath = rtrim($this->currentPrefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $fullPath = $this->normalizeRoutePath($fullPath);

        $this->validateRoutePath($fullPath);

        $route = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => array_merge($this->currentMiddleware, $middleware),
            'name'       => $name,
        ];

        self::$routes[] = $route;

        $this->currentMiddleware = [];
        $this->currentPrefix    = '';

        return new RouteBuilder($this, $route, array_key_last(self::$routes));
    }

    private function normalizeRoutePath(string $path): string
    {
        $parts = explode('/', $path);
        $clean = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..' && ! empty($clean)) {
                array_pop($clean);

                continue;
            }

            if ($part !== '..') {
                $clean[] = $part;
            }
        }

        return '/' . implode('/', $clean);
    }

    private function validateRoutePath(string $path): void
    {
        $segments = explode('/', trim($path, '/'));
        $seen = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            if (isset($seen[$segment])) {
                $cleaned = '/' . implode('/', array_unique($segments));
                if ($cleaned !== $path) {
                    self::$routes[array_key_last(self::$routes) - 1]['path'] = $cleaned;
                }

                return;
            }

            $seen[$segment] = true;
        }
    }

    public function buildRoute(string $method, string $path, string|callable $handler, array $middleware = [], ?string $name = null): RouteBuilder
    {
        $fullPath = rtrim($this->currentPrefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $fullPath = $this->normalizeRoutePath($fullPath);

        $this->validateRoutePath($fullPath);

        $route = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => array_merge($this->currentMiddleware, $middleware),
            'name'       => $name,
        ];

        self::$routes[] = $route;

        return new RouteBuilder($this, $route, array_key_last(self::$routes));
    }

    public function dispatch(): void
    {
        $method = request_method();
        $path   = request_path();
        $method = $method === 'GET' && ($_POST['_method'] ?? null) !== null
            ? strtoupper((string) $_POST['_method'])
            : $method;

        // Couche de contrôle centralisée (Control Layer) — chaque requête
        (new \App\Middleware\SystemControlMiddleware())->handle();

        // Protection CSRF globale sur les méthodes mutantes
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            (new \App\Middleware\CsrfMiddleware())->handle();
        }

        foreach (self::$routes as $index => $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['path'], $path);
            if ($params === null) {
                continue;
            }

            $this->runMiddleware($route['middleware']);
            $this->call($route['handler'], $params);

            return;
        }

        abort(404, 'Page introuvable');
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $spec) {
            $class = $spec;
            $args  = [];

            if (is_string($spec) && str_contains($spec, ':')) {
                [$class, $arg] = explode(':', $spec, 2);
                $args = [$arg];
            }

            $instance = new $class();
            if (method_exists($instance, 'handle')) {
                $instance->handle(...$args);
            }
        }
    }

    private function call(string|callable $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func($handler, ...array_values($params));

            return;
        }

        [$controller, $method] = explode('@', $handler);

        if (! class_exists($controller)) {
            $qualified = '\\App\\Controllers\\' . $controller;
            if (class_exists($qualified)) {
                $controller = $qualified;
            }
        }

        $instance = new $controller();
        $instance->$method(...array_values($params));
    }
}

/**
 * Fluent builder pour nommer les routes.
 */
final class RouteBuilder
{
    public function __construct(
        private readonly Router $router,
        private readonly array $route,
        private readonly int $index,
    ) {
    }

    public function name(string $name): self
    {
        Router::$routes[$this->index]['name'] = $name;

        return $this;
    }
}
