<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Contracts;

interface Authenticatable
{
    public function authorizeToken(): void;

    public function authorizeWithToken(string $token): ?static;

    public function logout(): void;
}
