<?php

namespace TDarkCoder\Framework\Form;

use TDarkCoder\Framework\Form\FieldTypes\InputField;
use TDarkCoder\Framework\Form\FieldTypes\TextareaField;

class Form
{
    public function end(): string
    {
        return '</form>';
    }

    public function input(string $attribute, ?string $label = null): Field
    {
        return new InputField($attribute, $label);
    }

    public function start(string $action, string $method): string
    {
        return sprintf('<form action="%s" method="%s">', $action, $method);
    }

    public function text(string $attribute, ?string $label = null): Field
    {
        return new TextareaField($attribute, $label);
    }
}