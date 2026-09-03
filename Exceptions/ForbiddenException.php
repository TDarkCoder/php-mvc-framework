<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Exceptions;

class ForbiddenException extends HttpException
{
    /** @var int */
    protected $code = 403;
    protected $message = 'Access forbidden';
}
