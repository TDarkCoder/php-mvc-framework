<?php

declare(strict_types=1);

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
    /**
     * Read a config value using dot notation, e.g. config('database.dsn').
     */
    function config(string $key, mixed $default = null): mixed
    {
        $config = app()->config;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }

            $config = $config[$segment];
        }

        return $config ?? $default;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            ob_start();

            var_dump($var);

            $dump = (string) ob_get_clean();

            echo PHP_SAPI === 'cli' ? $dump : '<pre style="background:#18181b;color:#fafafa;padding:1rem;overflow:auto">' . e($dump) . '</pre>';
        }

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
    /**
     * Read an environment variable, casting "true", "false", "null" and
     * "empty" to their PHP equivalents.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        return request()->old($key, $default);
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

if (!function_exists('route')) {
    function route(string $name, array $parameters = []): string
    {
        return app()->router->route($name, $parameters);
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
