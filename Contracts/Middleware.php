<?php

namespace TDarkCoder\Framework\Contracts;

use TDarkCoder\Framework\Http\Request;

interface Middleware
{
    public function handle(Request $request): bool;
}
