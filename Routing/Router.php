<?php

namespace TDarkCoder\Framework\Routing;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;
use TDarkCoder\Framework\Exceptions\NotFoundException;
use TDarkCoder\Framework\Http\Controller;
use TDarkCoder\Framework\Http\Middleware;

class Router implements RouterContract
{
    private array $currentRoute;
    private array $routes = [];

    public function delete(string $path, Closure|string|array $callback): self
    {
        $this->addRoute('delete', $path, $callback);

        return $this;
    }

    public function get(string $path, Closure|string|array $callback): self
    {
        $this->addRoute('get', $path, $callback);

        return $this;
    }

    public function middleware(string|array $middlewares = []): void
    {
        if (!empty($middlewares)) {
            if (is_string($middlewares)) {
                $middlewares = [$middlewares];
            }

            $this->routes[$this->currentRoute['method']][$this->currentRoute['path']][1] = $middlewares;
        }
    }

    public function post(string $path, Closure|string|array $callback): self
    {
        $this->addRoute('post', $path, $callback);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function resolve(): mixed
    {
        foreach ($this->routes[request()->method()] ?? [] as $route => $action) {
            [$callback, $middlewares] = $action;

            if ($this->matchUri($route)) {
                $globalMiddlewares = config('middlewares') ?? [];

                $this->applyMiddlewares($middlewares + $globalMiddlewares);

                return $this->handleCallback($callback, $route);
            }
        }

        throw new NotFoundException();
    }

    private function addRoute(string $method, string $path, Closure|string|array $callback): void
    {
        $this->routes[$method][$path] = [$callback, []];

        $this->currentRoute = [
            'method' => $method,
            'path' => $path,
        ];
    }

    private function applyControllerMiddlewares(Controller $controller, string $method): void
    {
        foreach ($controller->getMiddlewares() as $middleware => $methods) {
            $middleware = new $middleware();

            if ($middleware instanceof Middleware && $this->isMiddlewareApplicable($methods, $method)) {
                $middleware->handle(request());
            }
        }
    }

    private function applyMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $middleware = new $middleware();

            if ($middleware instanceof Middleware) {
                $middleware->handle(request());
            }
        }
    }

    /**
     * @throws Exception
     */
    private function attachRequestIfRequired(Controller $controller, string $method, array &$params): void
    {
        $request = array_filter(
            (new ReflectionClass($controller))->getMethod($method)->getParameters(),
            fn(ReflectionParameter $parameter): bool => $parameter->getName() === 'request',
        );

        if (!empty($request)) {
            $params['request'] = request();
        }
    }

    private function extractParameters(string $route): array
    {
        $params = [];
        $pathParts = explode('/', request()->path());
        $routeParts = explode('/', $route);

        foreach ($routeParts as $key => $routePart) {
            if (str_starts_with($routePart, '{') && str_ends_with($routePart, '}')) {
                $params[trim($routePart, '{}')] = $pathParts[$key];
            }
        }

        return $params;
    }

    /**
     * @throws Exception
     */
    private function handleCallback(Closure|string|array $callback, string $route): mixed
    {
        if (is_string($callback)) {
            return view($callback);
        }

        $params = $this->extractParameters($route);

        if (is_array($callback)) {
            [$controller, $method] = $callback;

            $controller = new $controller();

            $this->applyControllerMiddlewares($controller, $method);
            $this->attachRequestIfRequired($controller, $method, $params);

            $callback = [$controller, $method];
        }

        return call_user_func_array($callback, $params);
    }

    private function isMiddlewareApplicable(string|array $methods, string $method): bool
    {
        if (is_string($methods) && ($methods === '*' || $methods === $method)) {
            return true;
        }

        if (is_array($methods) && in_array($method, $methods)) {
            return true;
        }

        return false;
    }

    private function matchUri(string $route): bool
    {
        $pathParts = explode('/', request()->path());
        $routeParts = explode('/', $route);

        if (count($pathParts) !== count($routeParts)) {
            return false;
        }

        foreach ($routeParts as $key => $routePart) {
            if ($routePart !== $pathParts[$key] && !str_starts_with($routePart, '{')) {
                return false;
            }
        }

        return true;
    }
}