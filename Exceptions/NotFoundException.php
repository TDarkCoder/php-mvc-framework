<?php

namespace TDarkCoder\Framework\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    protected $code = 404;
    protected $message = 'Page not found';
}