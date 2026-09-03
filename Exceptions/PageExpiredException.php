<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Exceptions;

class PageExpiredException extends HttpException
{
    /** @var int */
    protected $code = 419;
    protected $message = 'Page expired';
}
