<?php

namespace TDarkCoder\Framework\Contracts;

interface View
{
    public function exists(string $view): bool;

    public function layout(?string $layout): static;

    public function render(string $view, array $params = []): string;
}
