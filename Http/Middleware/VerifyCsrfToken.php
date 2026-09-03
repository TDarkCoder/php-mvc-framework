<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Http\Middleware;

use Closure;
use TDarkCoder\Framework\Contracts\Middleware;
use TDarkCoder\Framework\Exceptions\PageExpiredException;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;

class VerifyCsrfToken implements Middleware
{
    protected array $except = [];

    /**
     * @throws PageExpiredException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $this->isReading($request)
            || $this->isException($request)
            || $this->tokensMatch($request)
        ) {
            return $next($request);
        }

        throw new PageExpiredException();
    }

    private function isReading(Request $request): bool
    {
        return in_array($request->method(), ['head', 'get', 'options'], true);
    }

    private function tokensMatch(Request $request): bool
    {
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

        return is_string($token) && hash_equals(session()->token(), $token);
    }

    private function isException(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->except as $pattern) {
            if ($pattern === $path || fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
