<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests\Fixtures;

use Closure;
use TDarkCoder\Framework\Contracts\Middleware;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;

class BlockingMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return new Response('blocked', 403);
    }
}
