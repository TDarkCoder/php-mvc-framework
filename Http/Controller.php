<?php

namespace TDarkCoder\Framework\Http;

class Controller
{
    protected array $middlewares = [];

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}