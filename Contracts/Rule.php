<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Contracts;

interface Rule
{
    public function passes(string $attribute, mixed $value, array $parameters, array $data): bool;

    /**
     * The error message. May contain :attribute and :param placeholders.
     */
    public function message(): string;
}
