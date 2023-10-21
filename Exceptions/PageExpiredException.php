<?php

namespace TDarkCoder\Framework\Exceptions;

use Exception;

class PageExpiredException extends Exception
{
    protected $code = 419;
    protected $message = 'Page expired';
}