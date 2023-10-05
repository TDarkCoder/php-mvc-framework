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
            $this->type,
            request()->getError($this->attribute) ? 'is-invalid' : '',
            $this->attribute,
            $this->attribute,
            request()->{$this->attribute} ?? $this->defaultValue,
        );
    }
}