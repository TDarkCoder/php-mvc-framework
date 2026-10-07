<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Enums;

enum InputTypes: string
{
    case Checkbox = 'checkbox';
    case Date = 'date';
    case Email = 'email';
    case File = 'file';
    case Hidden = 'hidden';
    case Number = 'number';
    case Password = 'password';
    case Text = 'text';
}
