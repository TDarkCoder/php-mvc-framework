<?php

use TDarkCoder\Framework\Application;

if (!function_exists('app')) {
    function app(): Application
    {
        return Application::$app;
    }
}

if (!function_exists('basePath')) {
    function basePath(string $path = ''): string
    {
        return app()->rootPath . $path;
    }
}

if (!function_exists('env')) {
    function env(string $key, string $default = ''): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header("Location: $path");

        exit;
    }
}

if (!function_exists('view')) {
    function view(string $path, array $params = []): string
    {
        return app()->view->render($path, $params);
    }
}