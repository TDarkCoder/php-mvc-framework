<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Http;

use TDarkCoder\Framework\Enums\SessionKeys;
use TDarkCoder\Framework\Exceptions\ValidationException;
use TDarkCoder\Framework\Validation\Validator;

class Request
{
    private array $body;
    private array $data;
    private array $query;

    public function __construct()
    {
        $this->query = $_GET;
        $this->body = $this->parseBody();
        $this->data = array_merge($this->query, $this->body);
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Validation errors flashed by the previous request.
     *
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return session()->getFlash(SessionKeys::Errors->value, []);
    }

    /**
     * Flash the current input for the next request, leaving out secrets.
     */
    public function flash(): void
    {
        $inputs = array_filter(
            $this->data,
            fn(string|int $key): bool => !str_contains((string) $key, 'password') && $key !== '_token',
            ARRAY_FILTER_USE_KEY,
        );

        session()->setFlash(SessionKeys::OldInput->value, $inputs);
    }

    public function getError(string $attribute): ?string
    {
        return $this->errors()[$attribute][0] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $_SERVER[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function isGet(): bool
    {
        return $this->method() === 'get';
    }

    public function isJson(): bool
    {
        return str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json');
    }

    public function isPost(): bool
    {
        return $this->method() === 'post';
    }

    public function method(): string
    {
        $method = strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');

        if ($method !== 'post') {
            return $method;
        }

        $override = strtolower((string) ($this->body['_method'] ?? ''));

        return in_array($override, ['put', 'patch', 'delete'], true) ? $override : $method;
    }

    public function old(string $attribute, mixed $default = null): mixed
    {
        return session()->getFlash(SessionKeys::OldInput->value, [])[$attribute] ?? $default;
    }

    public function only(array $keys): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->data[$key] ?? null;
        }

        return $results;
    }

    public function path(): string
    {
        $path = explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0];

        return rawurldecode($path) ?: '/';
    }

    public function previousUrl(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? $this->path();
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept', ''), 'json');
    }

    /**
     * Validate the input and return the validated attributes. Failures throw
     * a ValidationException which the application turns into a redirect
     * back with the errors and old input, or a 422 JSON response.
     *
     * @throws ValidationException
     */
    public function validate(array $rules, array $messages = []): array
    {
        return Validator::make($this->data, $rules, $messages)->validate();
    }

    private function parseBody(): array
    {
        $method = strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');

        if (in_array($method, ['get', 'head', 'options'], true)) {
            return [];
        }

        if ($this->isJson()) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);

            return is_array($decoded) ? $decoded : [];
        }

        if ($method === 'post') {
            return $_POST;
        }

        parse_str((string) file_get_contents('php://input'), $body);

        return $body;
    }
}