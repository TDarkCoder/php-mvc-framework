<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Exceptions;

class MethodNotAllowedException extends HttpException
{
    /** @var int */
    protected $code = 405;
    protected $message = 'Method not allowed';
}
