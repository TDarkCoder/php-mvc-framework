<?php

use TDarkCoder\Framework\Application;
use TDarkCoder\Framework\Exceptions\ForbiddenException;
use TDarkCoder\Framework\Exceptions\HttpException;
use TDarkCoder\Framework\Exceptions\NotFoundException;
use TDarkCoder\Framework\Exceptions\PageExpiredException;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;
use TDarkCoder\Framework\Session\Session;

if (!function_exists('abort')) {
    /**
     * @throws HttpException
     */
    function abort(int $code, string $message = ''): never
    {
        throw match ($code) {
            403 => new ForbiddenException($message),
            404 => new NotFoundException($message),
            419 => new PageExpiredException($message),
            default => new HttpException($message, $code),
        };
    }
}

if (!function_exists('app')) {
    function app(): Application
    {
        return Application::$app;
    }
}

if (!function_exists('back')) {
    function back(int $status = 302): Response
    {
        return redirect(request()->previousUrl(), $status);
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

if (!function_exists('dd')) {
    function dd(mixed $data): never
    {
        var_dump($data);

        exit(1);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('env')) {
    function env(string $key, string $default = ''): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $status = 302): Response
    {
        return Response::redirect($path, $status);
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return app()->request;
    }
}

if (!function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
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