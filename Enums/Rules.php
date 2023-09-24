<?php

namespace TDarkCoder\Framework\Enums;

enum Rules: string
{
    case Required = 'required';
    case Number = 'number';
    case Min = 'min';
    case Max = 'max';
    case Email = 'email';
    case GreaterOrEqual = 'gte';
    case LessOrEqual = 'lte';
    case Match = 'match';
    case Unique = 'unique';

    public function message(): string
    {
        return match ($this) {
            self::Required => 'The field is required',
            self::Email => 'The field must be email address',
            self::Number => 'The field value should be number',
            self::Min => 'Min length for the field should be {min}',
            self::Max => 'Max length for the field should be {max}',
            self::GreaterOrEqual => 'The field value should be greater or equal to {gte}',
            self::LessOrEqual => 'The field value should be less or equal to {lte}',
            self::Match => 'The field should the same as {match}',
            self::Unique => 'This {unique} already exists',
        };
    }
}