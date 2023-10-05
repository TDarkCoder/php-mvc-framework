<?php

namespace TDarkCoder\Framework\Form\FieldTypes;

use TDarkCoder\Framework\Form\Field;

class TextareaField extends Field
{
    protected function renderField(): string
    {
        return sprintf('
                <textarea class="form-control %s"
                          id="%s"
                          name="%s">%s</textarea>
        ',
            request()->getError('description') ? 'is-invalid' : '',
            $this->attribute,
            $this->attribute,
            request()->{$this->attribute} ?? $this->defaultValue,
        );
    }
}