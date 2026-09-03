<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Form\FieldTypes;

use TDarkCoder\Framework\Enums\InputTypes;
use TDarkCoder\Framework\Form\Field;

class InputField extends Field
{
    protected function renderField(): string
    {
        $value = in_array($this->type, [InputTypes::File, InputTypes::Password], true) ? '' : $this->value();

        return sprintf(
            '<input type="%s" class="form-control%s" id="%s" name="%s" value="%s"%s>',
            e($this->type->value),
            $this->error() ? ' is-invalid' : '',
            e($this->id()),
            e($this->attribute),
            e(is_scalar($value) ? $value : ''),
            $this->attributesString(),
        );
    }
}
