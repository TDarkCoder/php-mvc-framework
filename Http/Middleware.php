<?php

namespace TDarkCoder\Framework\Http;

interface Middleware
{
    public function handle(Request $request): bool;
}