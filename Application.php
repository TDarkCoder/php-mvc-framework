<?php

namespace TDarkCoder\Framework;

class Application
{
    public static self $app;
    public function __construct(public readonly string $rootPath, public readonly array $config)
    {
        self::$app = $this;
    }
    public function run(): never
    {
    }
}