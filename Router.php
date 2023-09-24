<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Exceptions\NotFoundException;

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
    }

    private function addRoute(string $method, string $path, mixed $callback): void
    {
        $this->routes[$method][$path] = [$callback, []];

        $this->currentRoute = [
            'method' => $method,
            'path' => $path,
        ];
    }

}