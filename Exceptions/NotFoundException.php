<?php

namespace TDarkCoder\Framework\Exceptions;

class NotFoundException extends HttpException
{
    protected $code = 404;
    protected $message = 'Page not found';
}
