<?php

namespace TDarkCoder\Framework\Contracts;

use Closure;
use TDarkCoder\Framework\Http\Response;
use TDarkCoder\Framework\Routing\Route;

interface Router
{
    public function any(string $path, Closure|string|array $action): Route;

    public function delete(string $path, Closure|string|array $action): Route;

    public function get(string $path, Closure|string|array $action): Route;

    /**
     * @param array{prefix?: string, middleware?: string|string[]} $attributes
     */
    public function group(array $attributes, Closure $routes): void;

    public function match(array $methods, string $path, Closure|string|array $action): Route;

    public function patch(string $path, Closure|string|array $action): Route;

    public function post(string $path, Closure|string|array $action): Route;

    public function put(string $path, Closure|string|array $action): Route;

    public function resolve(): Response;

    public function route(string $name, array $parameters = []): string;
}
