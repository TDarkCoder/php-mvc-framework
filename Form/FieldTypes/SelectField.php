<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Form\FieldTypes;

use TDarkCoder\Framework\Form\Field;

class SelectField extends Field
{
    /**
     * @param array<string|int, string> $options value => label
     */
    public function __construct(string $attribute, private readonly array $options, ?string $label = null)
    {
        parent::__construct($attribute, $label);
    }

    protected function renderField(): string
    {
        $selected = $this->value();
        $selected = is_array($selected) ? array_map('strval', $selected) : [(string) $selected];
        $options = '';

        foreach ($this->options as $value => $label) {
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                e($value),
                in_array((string) $value, $selected, true) ? ' selected' : '',
                e($label),
            );
        }

        return sprintf(
            '<select class="form-select%s" id="%s" name="%s"%s>%s</select>',
            $this->error() ? ' is-invalid' : '',
            e($this->id()),
            e($this->attribute),
            $this->attributesString(),
            $options,
        );
    }
}
