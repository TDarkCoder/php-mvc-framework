<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Http;

use Closure;
use TDarkCoder\Framework\Contracts\Middleware;
use TDarkCoder\Framework\Exceptions\ServerErrorException;

class Pipeline
{
    /**
     * @param array<class-string<Middleware>|Middleware> $middlewares
     */
    public function __construct(private array $middlewares = [])
    {
    }

    public function through(string|array $middlewares): static
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middlewares);

        return $this;
    }

    /**
     * Run the request through every middleware and finally the destination,
     * whose return value is converted into a Response.
     *
     * @throws ServerErrorException
     */
    public function then(Request $request, Closure $destination): Response
    {
        $core = static fn(Request $request): Response => Response::from($destination($request));

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn(Closure $next, string|Middleware $middleware): Closure => fn(Request $request): Response => $this->resolve($middleware)->handle($request, $next),
            $core,
        );

        return $pipeline($request);
    }

    /**
     * @throws ServerErrorException
     */
    private function resolve(string|Middleware $middleware): Middleware
    {
        $instance = is_string($middleware) ? new $middleware() : $middleware;

        if (!$instance instanceof Middleware) {
            throw new ServerErrorException(sprintf('%s must implement %s', get_debug_type($instance), Middleware::class));
        }

        return $instance;
    }
}
