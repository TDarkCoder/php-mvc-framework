<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Exceptions;

class NotFoundException extends HttpException
{
    /** @var int */
    protected $code = 404;
    protected $message = 'Page not found';
}
