<?php

namespace TDarkCoder\Framework\Exceptions;

use Exception;
use Throwable;

class HttpException extends Exception
{
    protected $code = 500;
    protected $message = 'Server error';

    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message === '' ? $this->message : $message, $this->code, $previous);
    }

    public function getStatusCode(): int
    {
        return (int) $this->code;
    }
}
