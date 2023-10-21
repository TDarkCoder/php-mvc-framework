<?php

namespace TDarkCoder\Framework\Session;

use TDarkCoder\Framework\Enums\SessionKeys;

class Session implements SessionContract
{
    private string $flash;

    public function __construct()
    {
        session_start();

        $this->flash = SessionKeys::Flash->value;
        $this->initializeFlashMessages();
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

    private function initializeFlashMessages(): void
    {
        foreach ($_SESSION[$this->flash] ?? [] as $key => $session) {
            $_SESSION[$this->flash][$key] = [
                'remove' => true,
                'value' => $session['value'],
            ];
        }
    }

    private function removeFlashMessages(): void
    {
        foreach ($_SESSION[$this->flash] ?? [] as $key => $value) {
            if ($value['remove'] === true) {
                unset($_SESSION[$this->flash][$key]);
            }
        }
    }
}