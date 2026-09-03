<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Enums;

enum MigrationFlags: string
{
    case Up = 'up';
    case Down = 'down';
    case Refresh = 'refresh';
}