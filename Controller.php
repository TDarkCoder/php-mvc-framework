<?php

namespace TDarkCoder\Framework;

class Controller
{
    protected array $middlewares = [];

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}