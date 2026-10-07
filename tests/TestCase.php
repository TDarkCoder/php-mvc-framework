<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use TDarkCoder\Framework\Application;

abstract class TestCase extends BaseTestCase
{
    protected string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = sys_get_temp_dir() . '/php-mvc-framework-' . bin2hex(random_bytes(4));

        mkdir($this->rootPath . '/views/layouts', 0777, true);

        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        unset(
            $_SERVER['HTTP_ACCEPT'],
            $_SERVER['CONTENT_TYPE'],
            $_SERVER['HTTP_REFERER'],
            $_SERVER['HTTP_X_CSRF_TOKEN'],
            $_SERVER['HTTP_USER_AGENT'],
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootPath);

        parent::tearDown();
    }

    /**
     * Boot an application for a simulated request. Every call behaves like a
     * new request while the sqlite database file persists for the test.
     */
    protected function app(
        string $method = 'GET',
        string $uri = '/',
        array $body = [],
        array $server = [],
        array $config = [],
    ): Application {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;

        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        $_GET = $query;
        $_POST = $body;

        foreach ($server as $key => $value) {
            $_SERVER[$key] = $value;
        }

        return new Application($this->rootPath, array_replace_recursive([
            'database' => ['dsn' => 'sqlite:' . $this->rootPath . '/database.sqlite'],
            'views' => ['path' => $this->rootPath . '/views'],
        ], $config));
    }

    protected function createUsersTable(Application $app): void
    {
        $app->database->pdo()->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT,
                password TEXT,
                role TEXT NULL
            )
        ');
    }

    protected function writeView(string $name, string $content): void
    {
        $file = $this->rootPath . '/views/' . str_replace('.', '/', $name) . '.php';

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        file_put_contents($file, $content);
    }

    private function removeDirectory(string $directory): void
    {
        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $item) {
            $path = "$directory/$item";

            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
