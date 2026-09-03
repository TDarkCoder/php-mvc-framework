<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Contracts;

use Closure;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;

interface Middleware
{
    /**
     * Handle the request and either return a Response or pass it on by
     * calling $next($request).
     */
    public function handle(Request $request, Closure $next): Response;
}
