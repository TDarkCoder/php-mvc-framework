<?php

namespace TDarkCoder\Framework\Enums;

enum InputTypes: string
{
    case Email = 'email';
    case Password = 'password';
    case Text = 'text';
    case Number = 'number';
    case Hidden = 'hidden';
}