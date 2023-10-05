<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Enums\SessionKeys;

class Session
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

    public function set(string $key, mixed $value, bool $isFlash = false): void
    {
        if ($isFlash) {
            $_SESSION[$this->flash][$key] = [
                'remove' => false,
                'value' => $value,
            ];
        }

        if (!$isFlash && $key !== $this->flash) {
            $_SESSION[$key] = $value;
        }
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$this->flash][$key]['value'] ?? $_SESSION[$key] ?? null;
    }

    public function unset(string $key): void
    {
        unset($_SESSION[$key]);
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