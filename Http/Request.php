<?php

namespace TDarkCoder\Framework\Http;

use TDarkCoder\Framework\Database\Model;
use TDarkCoder\Framework\Enums\Rules;
use TDarkCoder\Framework\Enums\SessionKeys;

class Request
{
    private array $body;
    private array $data;
    private array $errors = [];
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

    public function getError(string $attribute): string|bool
    {
        return session()->getFlash(SessionKeys::OldInput->value)['errors'][$attribute][0] ?? false;
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

    public function old(string $attribute)
    {
        return session()->getFlash(SessionKeys::OldInput->value)['inputs'][$attribute] ?? null;
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

    public function validate(array $data): bool
    {
        foreach ($data as $attribute => $rules) {
            $value = $this->data[$attribute] ?? '';
            $value = is_array($value) ? $value : (string) $value;

            $rules = explode('|', $rules);

            foreach ($rules as $rule) {
                $newRules = explode(':', $rule);

                if (count($newRules) > 1) {
                    [$rule, $indicator] = $newRules;

                    if ($rule === Rules::Min->value && strlen($value) < $indicator) {
                        $this->addError($attribute, Rules::Min, $indicator);
                    }

                    if ($rule === Rules::Max->value && strlen($value) > $indicator) {
                        $this->addError($attribute, Rules::Max, $indicator);
                    }

                    if ($rule === Rules::LessOrEqual->value && $value > $indicator) {
                        $this->addError($attribute, Rules::LessOrEqual, $indicator);
                    }

                    if ($rule === Rules::GreaterOrEqual->value && $value < $indicator) {
                        $this->addError($attribute, Rules::GreaterOrEqual, $indicator);
                    }

                    if ($rule === Rules::Match->value && $value !== $this->{$indicator}) {
                        $this->addError($attribute, Rules::Match, $indicator);
                    }

                    if ($rule === Rules::Unique->value) {
                        $object = new $indicator();

                        if ($object instanceof Model && $object->findOne([$attribute => $value])) {
                            $this->addError($attribute, Rules::Unique, $attribute);
                        }
                    }
                } else {
                    if ($rule === Rules::Email->value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($attribute, Rules::Email);
                    }

                    if ($rule === Rules::Required->value && in_array($value, [null, '', [], false], true)) {
                        $this->addError($attribute, Rules::Required);
                    }

                    if ($rule === Rules::Number->value && !is_numeric($value)) {
                        $this->addError($attribute, Rules::Number);
                    }
                }
            }
        }

        session()->setFlash(SessionKeys::OldInput->value, [
            'inputs' => $this->data,
            'errors' => $this->errors,
        ]);

        if (!empty($this->errors)) {
            redirect($this->previousUrl());
        }

        return true;
    }

    private function addError(string $attribute, Rules $rule, string $indicator = ''): void
    {
        $this->errors[$attribute][] = str_replace("{{$rule->value}}", $indicator, $rule->message());
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