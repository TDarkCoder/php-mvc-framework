<?php

namespace TDarkCoder\Framework\Exceptions;

class MethodNotAllowedException extends HttpException
{
    protected $code = 405;
    protected $message = 'Method not allowed';
}
