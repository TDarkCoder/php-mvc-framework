<?php

namespace TDarkCoder\Framework\Exceptions;

class ViewNotFoundException extends HttpException
{
    protected $code = 500;
    protected $message = 'View not found';
}
