<?php

namespace TDarkCoder\Framework\Views;

interface ViewContract
{
    public const DEFAULT_TITLE = 'home';

    public function render(string $view, array $params): string;
}