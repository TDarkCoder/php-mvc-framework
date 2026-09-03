<?php

namespace TDarkCoder\Framework\Http\Middleware;

use TDarkCoder\Framework\Exceptions\PageExpiredException;
use TDarkCoder\Framework\Http\Middleware;
use TDarkCoder\Framework\Http\Request;

class VerifyCsrfToken implements Middleware
{
    protected array $except = [];

    /**
     * @throws PageExpiredException
     */
    public function handle(Request $request): bool
    {
        if (
            $this->isReading($request)
            || $this->isException($request)
            || $this->tokensMatch($request)
        ) {
            return true;
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
