<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Contracts;

interface Session
{
    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;

    public function getFlash(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    public function hasFlash(string $key): bool;

    public function invalidate(): void;

    public function regenerate(): void;

    public function regenerateToken(): void;

    public function remove(string $key): void;

    public function removeFlash(string $key): void;

    public function set(string $key, mixed $value): void;

    public function setFlash(string $key, mixed $value): void;

    public function token(): string;
}
