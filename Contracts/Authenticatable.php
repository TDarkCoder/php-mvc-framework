<?php

namespace TDarkCoder\Framework\Contracts;

interface Authenticatable
{
    public function authorizeToken(): void;

    public function authorizeWithToken(string $token): ?static;

    public function logout(): void;
}
