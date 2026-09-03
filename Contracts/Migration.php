<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Contracts;

interface Migration
{
    public function up(): string;

    public function down(): string;
}
