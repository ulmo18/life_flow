<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;

final class Router
{
    /** @var array<string, array<string, array{handler: callable, middleware: string[]}>> */
    private array $routes = [];

    /** @var array<string, class-string<MiddlewareInterface>> */
    private array $middlewareAliases = [];

    public function aliasMiddleware(string $alias, string $className): void
    {
        $this->middlewareAliases[$alias] = $className;
    }

    /** @param string[] $middleware */
    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param string[] $middleware */
    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param string[] $middleware */
    private function add(string $method, string $path, callable $handler, array $middleware = []): void
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $route = $this->routes[$method][$path] ?? null;
        if ($route === null) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        foreach ($route['middleware'] as $alias) {
            $className = $this->middlewareAliases[$alias] ?? null;
            if ($className === null) {
                http_response_code(500);
                echo 'Middleware not registered.';
                return;
            }

            $middleware = new $className();
            if (!$middleware->handle()) {
                return;
            }
        }

        call_user_func($route['handler']);
    }
}
