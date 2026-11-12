<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->add('POST', $path, $handler, $middlewares);
    }

    public function add(string $method, string $path, callable|array $handler, array $middlewares = []): self
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath !== '/' && str_ends_with($normalizedPath, '/')) {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalizedPath,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $reqMethod = $request->getMethod();
        $reqPath = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $reqMethod) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $reqPath, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                return $this->runPipeline($request, $route, $params);
            }
        }

        return Response::json([
            'error' => 'Ruta no encontrada.',
            'path' => $reqPath,
            'method' => $reqMethod,
        ], 404);
    }

    private function runPipeline(Request $request, array $route, array $urlParams): Response
    {
        $handler = $route['handler'];
        $middlewares = $route['middlewares'];

        $coreAction = function (Request $req) use ($handler, $urlParams): Response {
            if (is_array($handler) && count($handler) === 2) {
                [$class, $method] = $handler;
                $instance = is_string($class) ? new $class() : $class;
                return $instance->$method($req, $urlParams);
            }

            return $handler($req, $urlParams);
        };

        // Envolver en pipeline de middlewares
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (callable $next, $middleware) {
                return function (Request $req) use ($middleware, $next): Response {
                    if (is_callable($middleware)) {
                        return $middleware($req, $next);
                    }
                    if (is_array($middleware) && count($middleware) === 2) {
                        [$class, $method] = $middleware;
                        $instance = is_string($class) ? new $class() : $class;
                        return $instance->$method($req, $next);
                    }
                    if (is_object($middleware) && method_exists($middleware, 'handle')) {
                        return $middleware->handle($req, $next);
                    }
                    return $next($req);
                };
            },
            $coreAction
        );

        return $pipeline($request);
    }
}
