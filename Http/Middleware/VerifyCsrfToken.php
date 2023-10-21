<?php

namespace TDarkCoder\Framework\Http\Middleware;

use TDarkCoder\Framework\Enums\SessionKeys;
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
            || $this->tokensMatch($request)
            || $this->isException($request)
        ) {
            return true;
        }

        throw new PageExpiredException();
    }

    private function isReading(Request $request): bool
    {
        return in_array($request->method(), [
            'head',
            'get',
            'options',
            '',
        ]);
    }

    private function tokensMatch(Request $request): bool
    {
        if (!$request->has('_token')) {
            return false;
        }

        return $request->get('_token') === session()->get(SessionKeys::Token->value);
    }

    private function isException(Request $request): bool
    {
        return in_array($request->path(), $this->except);
    }
}