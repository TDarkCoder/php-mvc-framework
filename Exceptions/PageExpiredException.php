<?php

namespace TDarkCoder\Framework\Exceptions;

class PageExpiredException extends HttpException
{
    protected $code = 419;
    protected $message = 'Page expired';
}
