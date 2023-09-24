<?php

namespace TDarkCoder\Framework\Exceptions;

use Exception;

class ForbiddenException extends Exception
{
    protected $code = 403;
    protected $message = 'Access forbidden';
}