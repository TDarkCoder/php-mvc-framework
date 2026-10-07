<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Form;

use TDarkCoder\Framework\Form\FieldTypes\CheckboxField;
use TDarkCoder\Framework\Form\FieldTypes\InputField;
use TDarkCoder\Framework\Form\FieldTypes\SelectField;
use TDarkCoder\Framework\Form\FieldTypes\TextareaField;

class Form
{
    public function checkbox(string $attribute, ?string $label = null): CheckboxField
    {
        return new CheckboxField($attribute, $label);
    }

    public function end(): string
    {
        return '</form>';
    }

    public function input(string $attribute, ?string $label = null): InputField
    {
        return new InputField($attribute, $label);
    }

    /**
     * @param array<string|int, string> $options value => label
     */
    public function select(string $attribute, array $options, ?string $label = null): SelectField
    {
        return new SelectField($attribute, $options, $label);
    }

    /**
     * Open a form. Non-GET forms get the Csrf field automatically and PUT,
     * PATCH and DELETE are sent as POST with a _method field.
     */
    public function start(string $action, string $method = 'POST', array $attributes = []): string
    {
        $method = strtoupper($method);
        $formMethod = $method === 'GET' ? 'GET' : 'POST';
        $html = '';

        foreach ($attributes as $name => $value) {
            $html .= $value === true ? ' ' . e($name) : sprintf(' %s="%s"', e($name), e($value));
        }

        $html = sprintf('<form action="%s" method="%s"%s>', e($action), $formMethod, $html);

        if ($formMethod === 'POST') {
            $html .= csrf_field();
        }

        if (!in_array($method, ['GET', 'POST'], true)) {
            $html .= method_field($method);
        }

        return $html;
    }

    public function textarea(string $attribute, ?string $label = null): TextareaField
    {
        return new TextareaField($attribute, $label);
    }
}
