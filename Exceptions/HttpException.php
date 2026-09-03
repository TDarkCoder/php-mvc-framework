<?php

namespace TDarkCoder\Framework\Exceptions;

use Exception;
use Throwable;

class HttpException extends Exception
{
    protected $code = 500;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $code = $code ?: (int) $this->code;

        parent::__construct($message !== '' ? $message : $this->defaultMessage($code), $code, $previous);
    }

    public function getStatusCode(): int
    {
        return (int) $this->code;
    }

    private function defaultMessage(int $code): string
    {
        if ($this->message !== '') {
            return $this->message;
        }

        return match ($code) {
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Access forbidden',
            404 => 'Page not found',
            405 => 'Method not allowed',
            419 => 'Page expired',
            422 => 'Unprocessable content',
            429 => 'Too many requests',
            503 => 'Service unavailable',
            default => 'Server error',
        };
    }
}
