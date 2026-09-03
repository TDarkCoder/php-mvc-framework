<?php

namespace TDarkCoder\Framework\Exceptions;

class ServerErrorException extends HttpException
{
    protected $code = 500;
    protected $message = 'Server error';
}
