<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests\Fixtures;

use Closure;
use TDarkCoder\Framework\Contracts\Middleware;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;

class RecordingMiddleware implements Middleware
{
    public static array $log = [];

    public function handle(Request $request, Closure $next): Response
    {
        self::$log[] = 'recording';

        return $next($request)->header('X-Recorded', 'yes');
    }
}
