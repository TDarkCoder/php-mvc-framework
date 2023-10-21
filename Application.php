<?php

namespace TDarkCoder\Framework;

use Exception;
use TDarkCoder\Framework\Database\Database;
use TDarkCoder\Framework\Database\Model;
use TDarkCoder\Framework\Enums\SessionKeys;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Routing\Router;
use TDarkCoder\Framework\Routing\RouterContract;
use TDarkCoder\Framework\Services\AccessToken\AuthorizeTokens;
use TDarkCoder\Framework\Session\Session;
use TDarkCoder\Framework\Views\View;
use TDarkCoder\Framework\Views\ViewContract;

class Application
{
    public static self $app;
    public readonly Database $database;
    public readonly Request $request;
    public readonly RouterContract $router;
    public readonly Session $session;
    public ?Model $user = null;
    public readonly ViewContract $view;

    public function __construct(public readonly string $rootPath, public readonly array $config)
    {
        self::$app = $this;

        try {
            $this->initializeComponents();
            $this->initializeUser();
        } catch (Exception $exception) {
            echo $this->renderError($exception);

            exit(1);
        }
    }

    public function run(): never
    {
        try {
            echo $this->router->resolve();
        } catch (Exception $exception) {
            echo $this->renderError($exception);
        }

        exit(1);
    }

    private function initializeComponents(): void
    {
        $this->database = new Database();
        $this->request = new Request();
        $this->session = new Session();
        $this->router = new Router();
        $this->view = new View();
    }

    private function initializeUser(): void
    {
        if (!$user = config('user')) {
            return;
        }

        $user = new $user();

        if (!$user instanceof Model
            || !class_uses($user, AuthorizeTokens::class)
            || !$this->session->has(SessionKeys::Token->value)) {
            return;
        }

        $this->user = $user->authorizeWithToken($this->session->get(SessionKeys::Token->value));

        if (!$this->user) {
            $this->session->remove(SessionKeys::Token->value);
        }
    }

    private function renderError(Exception $exception): string
    {
        $file = null;

        if (file_exists(basePath("/views/_errors/{$exception->getCode()}.php"))) {
            $file = "_errors/{$exception->getCode()}";
        }

        if (is_null($file) && file_exists(basePath('/views/_errors.php'))) {
            $file = '_errors';
        }

        if (!isset($this->view) || is_null($file)) {
            ob_start();

            include_once __DIR__ . '/Views/_errors.php';

            return ob_get_clean();
        }

        return $this->view->render($file, compact('exception'));
    }
}