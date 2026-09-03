<?php

namespace TDarkCoder\Framework\Enums;

enum SessionKeys: string
{
    case AuthToken = '_auth_token';
    case CsrfToken = '_csrf_token';
    case Errors = '_errors';
    case Flash = '_flash';
    case OldInput = '_old_input';
}
