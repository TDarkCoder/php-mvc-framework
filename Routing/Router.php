<?php

namespace TDarkCoder\Framework\Routing;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;
use TDarkCoder\Framework\Contracts\Router as RouterContract;
use TDarkCoder\Framework\Exceptions\NotFoundException;
use TDarkCoder\Framework\Http\Controller;
use TDarkCoder\Framework\Http\Pipeline;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;

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
    public function resolve(): Response
    {
        $request = request();

        foreach ($this->routes[$request->method()] ?? [] as $route => $action) {
            [$callback, $middlewares] = $action;

            if ($this->matchUri($route)) {
                $middlewares = array_merge(config('middlewares') ?? [], $middlewares);

                return (new Pipeline($middlewares))->then(
                    $request,
                    fn(Request $request): mixed => $this->handleCallback($callback, $route),
                );
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

    private function controllerMiddlewares(Controller $controller, string $method): array
    {
        $middlewares = [];

        foreach ($controller->getMiddlewares() as $middleware => $methods) {
            if ($this->isMiddlewareApplicable($methods, $method)) {
                $middlewares[] = $middleware;
            }
        }

        return $middlewares;
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

            $this->attachRequestIfRequired($controller, $method, $params);

            return (new Pipeline($this->controllerMiddlewares($controller, $method)))->then(
                request(),
                fn(): mixed => call_user_func_array([$controller, $method], $params),
            );
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