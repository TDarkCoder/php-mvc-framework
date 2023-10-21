<?php

namespace TDarkCoder\Framework\Session;

interface SessionContract
{
    public function __construct();

    public function __destruct();

    public function set(string $key, mixed $value): void;

    public function get(string $key): mixed;

    public function remove(string $key): void;

    public function has(string $key): bool;

    public function setFlash(string $key, mixed $value): void;

    public function getFlash(string $key): mixed;

    public function removeFlash(string $key): void;

    public function hasFlash(string $key): bool;
}