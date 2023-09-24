<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Enums\Rules;

class Request
{
    protected array $data = [];

    public function __construct()
    {
        if ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $this->data[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        if ($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $this->data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
    }

    public function only(array $keys): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->data[$key] ?? null;
        }

        return $results;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function isGet(): bool
    {
        return $this->method() === 'get';
    }

    public function isPost(): bool
    {
        return $this->method() === 'post';
    }

    public function method(): string
    {
        return strtolower($this->data['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? '');
    }

    public function path(): string
    {
        $path = $_SERVER['REQUEST_URI'];
        $queryPosition = strpos($path, '?');

        if (!$queryPosition) {
            return $path;
        }

        return substr($path, 0, $queryPosition);
    }

    public function __set(string $name, mixed $value)
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}