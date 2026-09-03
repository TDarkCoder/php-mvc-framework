<?php

namespace TDarkCoder\Framework\Views;

interface ViewContract
{
    public function layout(?string $layout): static;

    public function render(string $view, array $params = []): string;
}
