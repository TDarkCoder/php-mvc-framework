<?php

namespace TDarkCoder\Framework\Enums;

enum SessionKeys: string
{
    case Token = 'token';
    case Flash = 'flash_message';
    case OldInput = 'old_input';
}