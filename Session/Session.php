<?php

namespace TDarkCoder\Framework\Session;

use Exception;
use TDarkCoder\Framework\Contracts\Session as SessionContract;
use TDarkCoder\Framework\Enums\SessionKeys;

class Session implements SessionContract
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->start();
        $this->ageFlashData();

        if (!isset($_SESSION[SessionKeys::CsrfToken->value])) {
            $this->regenerateToken();
        }
    }

    public function all(): array
    {
        return $_SESSION;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $flash = $_SESSION[SessionKeys::Flash->value] ?? [];

        return $flash['old'][$key] ?? $flash['new'][$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function hasFlash(string $key): bool
    {
        $flash = $_SESSION[SessionKeys::Flash->value] ?? [];

        return isset($flash['old'][$key]) || isset($flash['new'][$key]);
    }

    public function invalidate(): void
    {
        $_SESSION = [];

        $this->regenerate();
        $this->regenerateToken();
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * @throws Exception
     */
    public function regenerateToken(): void
    {
        $_SESSION[SessionKeys::CsrfToken->value] = bin2hex(random_bytes(32));
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function removeFlash(string $key): void
    {
        unset($_SESSION[SessionKeys::Flash->value]['old'][$key], $_SESSION[SessionKeys::Flash->value]['new'][$key]);
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function setFlash(string $key, mixed $value): void
    {
        $_SESSION[SessionKeys::Flash->value]['new'][$key] = $value;
    }

    public function token(): string
    {
        return $_SESSION[SessionKeys::CsrfToken->value];
    }

    /**
     * Flash data written during the previous request becomes readable now
     * and is dropped at the start of the next request.
     */
    private function ageFlashData(): void
    {
        $flash = $_SESSION[SessionKeys::Flash->value] ?? [];

        $_SESSION[SessionKeys::Flash->value] = [
            'old' => $flash['new'] ?? [],
            'new' => [],
        ];
    }

    private function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    private function start(): void
    {
        if (PHP_SAPI === 'cli') {
            $_SESSION ??= [];

            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start(array_merge([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => $this->isSecure(),
            'use_only_cookies' => true,
            'use_strict_mode' => true,
        ], config('session') ?? []));
    }
}
