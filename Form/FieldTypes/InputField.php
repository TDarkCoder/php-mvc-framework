<?php

namespace TDarkCoder\Framework\Form\FieldTypes;

use TDarkCoder\Framework\Form\Field;

class InputField extends Field
{
    protected function renderField(): string
    {
        return sprintf('
            <input type="%s"
                   class="form-control %s"
                   id="%s"
                   name="%s"
                   value="%s">
        ',
            e($this->type),
            request()->getError($this->attribute) ? 'is-invalid' : '',
            e($this->attribute),
            e($this->attribute),
            e(request()->old($this->attribute) ?? $this->defaultValue),
        );
    }
}