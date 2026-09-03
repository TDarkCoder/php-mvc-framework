<?php

namespace TDarkCoder\Framework\Form\FieldTypes;

use TDarkCoder\Framework\Form\Field;

class TextareaField extends Field
{
    protected function renderField(): string
    {
        $value = $this->value();

        return sprintf(
            '<textarea class="form-control%s" id="%s" name="%s"%s>%s</textarea>',
            $this->error() ? ' is-invalid' : '',
            e($this->id()),
            e($this->attribute),
            $this->attributesString(),
            e(is_scalar($value) ? $value : ''),
        );
    }
}
