<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Enums\Rules;

class Request
{
    protected array $data = [];
    protected array $errors = [];

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

    public function validate(array $data): bool
    {
        foreach ($data as $attribute => $rules) {
            $value = $this->data[$attribute] ?? '';

            $rules = explode('|', $rules);

            foreach ($rules as $rule) {
                $newRules = explode(':', $rule);

                if (count($newRules) > 1) {
                    [$rule, $action] = $newRules;

                    if ($rule === Rules::Min->value && strlen($value) < $action) {
                        $this->addError($attribute, Rules::Min, $action);
                    }

                    if ($rule === Rules::Max->value && strlen($value) > $action) {
                        $this->addError($attribute, Rules::Min, $action);
                    }

                    if ($rule === Rules::LessOrEqual->value && $value > $action) {
                        $this->addError($attribute, Rules::LessOrEqual, $action);
                    }

                    if ($rule === Rules::GreaterOrEqual->value && $value < $action) {
                        $this->addError($attribute, Rules::GreaterOrEqual, $action);
                    }

                    if ($rule === Rules::Match->value && $value !== $this->{$action}) {
                        $this->addError($attribute, Rules::Match, $action);
                    }

                    if ($rule === Rules::Unique->value) {
                        $object = new $action();

                        if ($object instanceof Model && $object->findOne([$attribute => $value])) {
                            $this->addError($attribute, Rules::Unique, $attribute);
                        }
                    }
                } else {
                    if ($rule === Rules::Email->value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($attribute, Rules::Email);
                    }

                    if ($rule === Rules::Required->value && !$value) {
                        $this->addError($attribute, Rules::Required);
                    }

                    if ($rule === Rules::Number->value && !is_numeric($value)) {
                        $this->addError($attribute, Rules::Number);
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function getError(string $attribute): string
    {
        return $this->errors[$attribute][0] ?? false;
    }

    private function addError(string $attribute, Rules $rule, string $action = ''): void
    {
        $this->errors[$attribute][] = str_replace("{{$rule->value}}", $action, $rule->message()) ?? 'Unknown error';
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