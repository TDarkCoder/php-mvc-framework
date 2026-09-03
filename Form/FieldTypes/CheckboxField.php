<?php

namespace TDarkCoder\Framework\Form\FieldTypes;

use TDarkCoder\Framework\Enums\InputTypes;
use TDarkCoder\Framework\Form\Field;

class CheckboxField extends Field
{
    protected InputTypes $type = InputTypes::Checkbox;

    public function __toString(): string
    {
        return sprintf(
            '<div class="form-check">%s<label class="form-check-label" for="%s">%s</label><div class="invalid-feedback">%s</div></div>',
            $this->renderField(),
            e($this->id()),
            e($this->label()),
            e($this->error()),
        );
    }

    public function checked(bool $checked = true): static
    {
        return $this->default($checked ? '1' : '');
    }

    protected function renderField(): string
    {
        return sprintf(
            '<input type="checkbox" class="form-check-input%s" id="%s" name="%s" value="1"%s%s>',
            $this->error() ? ' is-invalid' : '',
            e($this->id()),
            e($this->attribute),
            $this->value() ? ' checked' : '',
            $this->attributesString(),
        );
    }
}
