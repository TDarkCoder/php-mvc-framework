<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Form;

use TDarkCoder\Framework\Enums\InputTypes;

abstract class Field
{
    protected array $attributes = [];
    protected string $defaultValue = '';
    protected InputTypes $type = InputTypes::Text;

    public function __construct(protected string $attribute, protected ?string $label = null)
    {
    }

    abstract protected function renderField(): string;

    public function __toString(): string
    {
        if ($this->type === InputTypes::Hidden) {
            return $this->renderField();
        }

        return sprintf(
            '<label for="%s" class="form-label">%s</label>%s<div class="invalid-feedback">%s</div>',
            e($this->id()),
            e($this->label()),
            $this->renderField(),
            e($this->error()),
        );
    }

    /**
     * Add any extra HTML attribute, e.g. attribute('autocomplete', 'off').
     */
    public function attribute(string $name, string|int|bool $value = true): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function date(): static
    {
        $this->type = InputTypes::Date;

        return $this;
    }

    public function default(string|int|float $value): static
    {
        $this->defaultValue = (string) $value;

        return $this;
    }

    public function email(): static
    {
        $this->type = InputTypes::Email;

        return $this;
    }

    public function file(): static
    {
        $this->type = InputTypes::File;

        return $this;
    }

    public function hidden(): static
    {
        $this->type = InputTypes::Hidden;

        return $this;
    }

    public function number(): static
    {
        $this->type = InputTypes::Number;

        return $this;
    }

    public function password(): static
    {
        $this->type = InputTypes::Password;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        return $this->attribute('placeholder', $placeholder);
    }

    public function required(): static
    {
        return $this->attribute('required');
    }

    public function token(): static
    {
        $this->attribute = '_token';

        return $this->hidden()->default(csrf_token());
    }

    /**
     * Render the extra attributes; boolean true renders the bare name.
     */
    protected function attributesString(): string
    {
        $html = '';

        foreach ($this->attributes as $name => $value) {
            if ($value === false) {
                continue;
            }

            $html .= $value === true
                ? ' ' . e($name)
                : sprintf(' %s="%s"', e($name), e($value));
        }

        return $html;
    }

    protected function error(): ?string
    {
        return request()->getError($this->name());
    }

    protected function id(): string
    {
        return (string) preg_replace('/[^A-Za-z0-9_-]+/', '_', $this->name());
    }

    protected function label(): string
    {
        return $this->label ?? ucfirst(str_replace('_', ' ', $this->name()));
    }

    /**
     * The attribute without an array suffix, used for errors and old input.
     */
    protected function name(): string
    {
        return rtrim($this->attribute, '[]');
    }

    protected function value(): mixed
    {
        return request()->old($this->name()) ?? $this->defaultValue;
    }
}
