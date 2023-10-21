<?php

use TDarkCoder\Framework\Application;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Session\Session;

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

if (!function_exists('config')) {
    function config(string $key): mixed
    {
        $config = app()->config;
        $keys = explode('.', $key);

        foreach ($keys as $key) {
            $config = $config[$key] ?? null;

            if (is_null($config)) {
                return null;
            }
        }

        return $config;
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

        exit(1);
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return app()->request;
    }
}

if (!function_exists('session')) {
    function session(): Session
    {
        return app()->session;
    }
}

if (!function_exists('view')) {
    function view(string $path, array $params = []): string
    {
        return app()->view->render($path, $params);
    }
}