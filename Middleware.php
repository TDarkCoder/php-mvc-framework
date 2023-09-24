<?php

namespace TDarkCoder\Framework;

interface Middleware
{
    public function handle(Request $request): mixed;
}