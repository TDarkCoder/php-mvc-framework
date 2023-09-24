<?php

namespace TDarkCoder\Framework\Exceptions;

use Exception;

class ServerErrorException extends Exception
{
    protected $code = 500;
    protected $message = 'Server error';
}