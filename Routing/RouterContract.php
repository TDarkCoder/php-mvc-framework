<?php

namespace TDarkCoder\Framework\Routing;

use Closure;

interface RouterContract
{
    public function delete(string $path, Closure|string|array $callback): self;

    public function get(string $path, Closure|string|array $callback): self;

    public function middleware(string|array $middlewares = []): void;

    public function post(string $path, Closure|string|array $callback): self;

    public function resolve(): mixed;
}