<?php

namespace TDarkCoder\Framework\Session;

use Exception;
use TDarkCoder\Framework\Enums\SessionKeys;

class Session implements SessionContract
{
    private string $flash;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->start();

        $this->flash = SessionKeys::Flash->value;
        $this->initializeFlashMessages();

        if (!isset($_SESSION[SessionKeys::CsrfToken->value])) {
            $this->regenerateToken();
        }
    }

    public function __destruct()
    {
        $this->removeFlashMessages();
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
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

    public function setFlash(string $key, mixed $value): void
    {
        $_SESSION[$this->flash][$key] = [
            'remove' => false,
            'value' => $value,
        ];
    }

    public function getFlash(string $key): mixed
    {
        return $_SESSION[$this->flash][$key]['value'] ?? null;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION[$this->flash][$key]);
    }

    public function removeFlash(string $key): void
    {
        unset($_SESSION[$this->flash][$key]);
    }

    public function token(): string
    {
        return $_SESSION[SessionKeys::CsrfToken->value];
    }

    private function initializeFlashMessages(): void
    {
        foreach ($_SESSION[$this->flash] ?? [] as $key => $session) {
            $_SESSION[$this->flash][$key] = [
                'remove' => true,
                'value' => $session['value'],
            ];
        }
    }

    private function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    private function removeFlashMessages(): void
    {
        foreach ($_SESSION[$this->flash] ?? [] as $key => $value) {
            if ($value['remove'] === true) {
                unset($_SESSION[$this->flash][$key]);
            }
        }
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
