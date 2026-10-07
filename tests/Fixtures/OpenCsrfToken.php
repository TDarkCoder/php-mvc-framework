<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests\Fixtures;

use TDarkCoder\Framework\Http\Middleware\VerifyCsrfToken;

class OpenCsrfToken extends VerifyCsrfToken
{
    protected array $except = ['/webhooks/*'];
}
