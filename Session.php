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

        foreach ($_SESSION[$this->flash] ?? [] as $key => $session) {
            $_SESSION[$this->flash][$key] = [
                'remove' => true,
                'value' => $session['value'],
            ];
        }
    }

    public function set(string $key, mixed $value, bool $isFlash = false): void
    {
        if (!$isFlash) {
            $_SESSION[$key] = $value;
        } else {
            $_SESSION[$this->flash][$key] = [
                'remove' => false,
                'value' => $value,
            ];
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

    public function __destruct()
    {
        foreach ($_SESSION[$this->flash] ?? [] as $key => $value) {
            if ($value['remove'] === true) {
                unset($_SESSION[$this->flash][$key]);
            }
        }
    }
}