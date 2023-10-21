<?php

namespace TDarkCoder\Framework\Form;

use TDarkCoder\Framework\Enums\InputTypes;
use TDarkCoder\Framework\Enums\SessionKeys;

abstract class Field
{
    protected string $type;
    protected string $defaultValue = '';

    protected abstract function renderField(): string;

    public function __construct(protected string $attribute, protected ?string $label)
    {
        $this->type = InputTypes::Text->value;
    }

    public function __toString(): string
    {
        return sprintf('
            <label for="%s" class="form-label %s">%s</label>
            %s
            <div class="invalid-feedback">
                %s
            </div>
        ',
            $this->attribute,
            $this->type === InputTypes::Hidden->value ? 'd-none' : '',
            $this->label ?? ucfirst($this->attribute),
            $this->renderField(),
            request()->getError($this->attribute),
        );
    }

    public function default(string $value): static
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function email(): static
    {
        $this->type = InputTypes::Email->value;

        return $this;
    }

    public function hidden(): static
    {
        $this->type = InputTypes::Hidden->value;

        return $this;
    }

    public function number(): static
    {
        $this->type = InputTypes::Number->value;

        return $this;
    }

    public function password(): static
    {
        $this->type = InputTypes::Password->value;

        return $this;
    }

    public function token(): static
    {
        $this->hidden();
        $this->default(session()->get(SessionKeys::Token->value));

        return $this;
    }
}