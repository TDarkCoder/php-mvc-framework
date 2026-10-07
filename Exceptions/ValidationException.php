<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Exceptions;

class ValidationException extends HttpException
{
    /** @var int */
    protected $code = 422;
    protected $message = 'The given data was invalid';

    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct();
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
