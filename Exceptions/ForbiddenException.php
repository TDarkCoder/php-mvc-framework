<?php

namespace TDarkCoder\Framework\Exceptions;

class ForbiddenException extends HttpException
{
    protected $code = 403;
    protected $message = 'Access forbidden';
}
