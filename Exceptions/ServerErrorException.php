<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Exceptions;

class ServerErrorException extends HttpException
{
    /** @var int */
    protected $code = 500;
    protected $message = 'Server error';
}
