<?php

namespace TDarkCoder\Framework\Contracts;

use Closure;
use TDarkCoder\Framework\Http\Response;

interface Router
{
    public function delete(string $path, Closure|string|array $callback): self;

    public function get(string $path, Closure|string|array $callback): self;

    public function middleware(string|array $middlewares = []): void;

    public function post(string $path, Closure|string|array $callback): self;

    public function resolve(): Response;
}
