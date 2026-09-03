<?php

namespace TDarkCoder\Framework\Enums;

enum SessionKeys: string
{
    case AuthToken = 'auth_token';
    case CsrfToken = 'csrf_token';
    case Flash = 'flash_message';
    case OldInput = 'old_input';
}
