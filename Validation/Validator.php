<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Validation;

use Closure;
use TDarkCoder\Framework\Contracts\Rule;
use TDarkCoder\Framework\Database\Model;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use TDarkCoder\Framework\Exceptions\ValidationException;

class Validator
{
    private const MESSAGES = [
        'boolean' => 'The :attribute field must be true or false',
        'confirmed' => 'The :attribute confirmation does not match',
        'email' => 'The :attribute must be a valid email address',
        'gte' => 'The :attribute must be greater than or equal to :param',
        'in' => 'The selected :attribute is invalid',
        'integer' => 'The :attribute must be an integer',
        'lte' => 'The :attribute must be less than or equal to :param',
        'match' => 'The :attribute must match :param',
        'max' => 'The :attribute may not be longer than :param characters',
        'min' => 'The :attribute must be at least :param characters',
        'number' => 'The :attribute must be a number',
        'numeric' => 'The :attribute must be a number',
        'regex' => 'The :attribute format is invalid',
        'required' => 'The :attribute field is required',
        'string' => 'The :attribute must be a string',
        'unique' => 'The :attribute has already been taken',
    ];

    /** @var array<string, array{rule: Closure|class-string<Rule>, message: ?string}> */
    private static array $extensions = [];

    private array $errors = [];
    private bool $ran = false;

    final public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $messages = [],
    ) {
    }

    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }

    /**
     * Register a custom rule usable as "name" or "name:param1,param2".
     * A closure receives ($attribute, $value, $parameters, $data).
     *
     * @param Closure|class-string<Rule> $rule
     */
    public static function extend(string $name, Closure|string $rule, ?string $message = null): void
    {
        self::$extensions[$name] = ['rule' => $rule, 'message' => $message];
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        $this->run();

        return $this->errors;
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function passes(): bool
    {
        $this->run();

        return $this->errors === [];
    }

    /**
     * @throws ValidationException
     */
    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }

        return $this->validated();
    }

    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }

    private function run(): void
    {
        if ($this->ran) {
            return;
        }

        $this->ran = true;

        foreach ($this->rules as $attribute => $rules) {
            $this->validateAttribute((string) $attribute, is_string($rules) ? explode('|', $rules) : (array) $rules);
        }
    }

    private function validateAttribute(string $attribute, array $rules): void
    {
        $value = $this->data[$attribute] ?? null;

        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                if (!$this->isBlank($value) && !$rule->passes($attribute, $value, [], $this->data)) {
                    $this->addError($attribute, $rule::class, [], $rule->message());
                }

                continue;
            }

            [$name, $parameters] = $this->parseRule((string) $rule);

            if ($name === '' || $name === 'nullable') {
                continue;
            }

            // Only "required" is checked against empty values; other rules
            // apply once a value is present.
            if ($name !== 'required' && $this->isBlank($value)) {
                continue;
            }

            if (!$this->check($name, $attribute, $value, $parameters)) {
                $this->addError($attribute, $name, $parameters);
            }
        }
    }

    /**
     * @throws ServerErrorException
     */
    private function check(string $name, string $attribute, mixed $value, array $parameters): bool
    {
        if (isset(self::$extensions[$name])) {
            $rule = self::$extensions[$name]['rule'];

            if ($rule instanceof Closure) {
                return (bool) $rule($attribute, $value, $parameters, $this->data);
            }

            $instance = new $rule();

            if (!$instance instanceof Rule) {
                throw new ServerErrorException("Validation rule [$name] must implement " . Rule::class);
            }

            return $instance->passes($attribute, $value, $parameters, $this->data);
        }

        $method = 'validate' . str_replace('_', '', ucwords($name, '_'));

        if (!method_exists($this, $method)) {
            throw new ServerErrorException("Unknown validation rule [$name]");
        }

        return $this->{$method}($attribute, $value, $parameters);
    }

    private function addError(string $attribute, string $rule, array $parameters, ?string $message = null): void
    {
        $message = $this->messages["$attribute.$rule"]
            ?? $this->messages[$rule]
            ?? $message
            ?? $this->extensionMessage($rule)
            ?? self::MESSAGES[$rule]
            ?? 'The :attribute is invalid';

        $this->errors[$attribute][] = str_replace(
            [':attribute', ':param', ':params'],
            [str_replace('_', ' ', $attribute), (string) ($parameters[0] ?? ''), implode(', ', $parameters)],
            $message,
        );
    }

    private function extensionMessage(string $rule): ?string
    {
        $extension = self::$extensions[$rule] ?? null;

        if ($extension === null) {
            return null;
        }

        if ($extension['message'] !== null || $extension['rule'] instanceof Closure) {
            return $extension['message'];
        }

        $instance = new ($extension['rule'])();

        return $instance instanceof Rule ? $instance->message() : null;
    }

    private function isBlank(mixed $value): bool
    {
        return is_null($value)
            || $value === []
            || (is_string($value) && trim($value) === '');
    }

    /**
     * @return array{0: string, 1: string[]}
     */
    private function parseRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [trim($rule), []];
        }

        [$name, $parameters] = explode(':', $rule, 2);
        $name = trim($name);

        if ($name === 'regex') {
            return [$name, [$parameters]];
        }

        return [$name, array_map('trim', explode(',', $parameters))];
    }

    private function size(mixed $value): int
    {
        if (is_array($value)) {
            return count($value);
        }

        return mb_strlen((string) $value);
    }

    private function validateBoolean(string $attribute, mixed $value, array $parameters): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }

    private function validateConfirmed(string $attribute, mixed $value, array $parameters): bool
    {
        return $value === ($this->data["{$attribute}_confirmation"] ?? null);
    }

    private function validateEmail(string $attribute, mixed $value, array $parameters): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateGte(string $attribute, mixed $value, array $parameters): bool
    {
        return is_numeric($value) && (float) $value >= (float) ($parameters[0] ?? 0);
    }

    private function validateIn(string $attribute, mixed $value, array $parameters): bool
    {
        return is_scalar($value) && in_array((string) $value, $parameters, true);
    }

    private function validateInteger(string $attribute, mixed $value, array $parameters): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateLte(string $attribute, mixed $value, array $parameters): bool
    {
        return is_numeric($value) && (float) $value <= (float) ($parameters[0] ?? 0);
    }

    private function validateMatch(string $attribute, mixed $value, array $parameters): bool
    {
        return $value === ($this->data[$parameters[0] ?? ''] ?? null);
    }

    private function validateMax(string $attribute, mixed $value, array $parameters): bool
    {
        return $this->size($value) <= (int) ($parameters[0] ?? 0);
    }

    private function validateMin(string $attribute, mixed $value, array $parameters): bool
    {
        return $this->size($value) >= (int) ($parameters[0] ?? 0);
    }

    private function validateNumber(string $attribute, mixed $value, array $parameters): bool
    {
        return is_numeric($value);
    }

    private function validateNumeric(string $attribute, mixed $value, array $parameters): bool
    {
        return is_numeric($value);
    }

    private function validateRegex(string $attribute, mixed $value, array $parameters): bool
    {
        return is_string($value) && preg_match($parameters[0] ?? '//', $value) === 1;
    }

    private function validateRequired(string $attribute, mixed $value, array $parameters): bool
    {
        return !$this->isBlank($value);
    }

    private function validateString(string $attribute, mixed $value, array $parameters): bool
    {
        return is_string($value);
    }

    /**
     * unique:ModelClass[,column[,ignoredId]]
     *
     * @throws ServerErrorException
     */
    private function validateUnique(string $attribute, mixed $value, array $parameters): bool
    {
        [$class, $column, $ignore] = array_pad($parameters, 3, null);
        $column = $column ?: $attribute;

        if (!is_string($class) || !is_subclass_of($class, Model::class)) {
            throw new ServerErrorException("The unique rule for [$attribute] needs a model class");
        }

        $existing = $class::findOne([$column => $value]);

        if (is_null($existing)) {
            return true;
        }

        return !is_null($ignore) && (string) $existing->{$existing->primaryKey} === (string) $ignore;
    }
}
