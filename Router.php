<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Exceptions\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Router
{
    private array $currentRoute;
    private array $routes = [];

    public function __construct(private readonly Request $request)
    {
    }

    public function get(string $path, mixed $callback): self
    {
        $this->addRoute('get', $path, $callback);

        return $this;
    }

    public function post(string $path, mixed $callback): self
    {
        $this->addRoute('post', $path, $callback);

        return $this;
    }

    public function delete(string $path, mixed $callback): self
    {
        $this->addRoute('delete', $path, $callback);

        return $this;
    }

    public function middleware(string|array $middlewares = []): void
    {
        if (!empty($middlewares)) {
            $path = $this->currentRoute['path'];
            $method = $this->currentRoute['method'];

            if (is_string($middlewares)) {
                $middlewares = [$middlewares];
            }

            $this->routes[$method][$path][1] = $middlewares;
        }
    }

    /**
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function resolve(): mixed
    {
        foreach ($this->routes[app()->request->method()] ?? [] as $route => $action) {
            [$callback, $middlewares] = $action;

            if ($this->matchUri($route)) {
                if (!$callback) {
                    throw new NotFoundException();
                }

                if (!empty($middlewares)) {
                    if (is_string($middlewares)) {
                        $middlewares = [$middlewares];
                    }

                    foreach ($middlewares as $middleware) {
                        (new $middleware())->handle($this->request);
                    }
                }

                if (is_string($callback)) {
                    return view($callback);
                }

                $params = $this->extractParameters($route);

                if (is_array($callback)) {
                    [$controller, $method] = $callback;

                    $requestExists = array_filter(
                        (new ReflectionClass($controller))->getMethod($method)->getParameters(),
                        fn(ReflectionParameter $parameter): bool => $parameter->getName() === 'request',
                    );

                    if (!!$requestExists) {
                        $params['request'] = $this->request;
                    }

                    $controller = new $controller();

                    foreach ($controller->getMiddlewares() as $middleware => $methods) {
                        $middleware = new $middleware();

                        if ($middleware instanceof Middleware && $this->isMiddlewareApplicable($methods, $method)) {
                            $middleware->handle($this->request);
                        }
                    }

                    $callback = [$controller, $method];
                }

                return call_user_func($callback, ...$params);
            }
        }

        throw new NotFoundException();
    }

    private function addRoute(string $method, string $path, mixed $callback): void
    {
        $this->routes[$method][$path] = [$callback, []];

        $this->currentRoute = [
            'method' => $method,
            'path' => $path,
        ];
    }

    private function matchUri(string $route): bool
    {
        $pathParts = explode('/', app()->request->path());
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

    private function extractParameters(string $route): array
    {
        $params = [];

        $pathParts = explode('/', app()->request->path());
        $routeParts = explode('/', $route);

        foreach ($routeParts as $key => $routePart) {
            if (str_starts_with($routePart, '{') && str_ends_with($routePart, '}')) {
                $params[trim($routePart, '{}')] = $pathParts[$key];
            }
        }

        return $params;
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
}