<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Database\Database;
use TDarkCoder\Framework\Database\Model;
use TDarkCoder\Framework\Enums\SessionKeys;
use TDarkCoder\Framework\Exceptions\HttpException;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Routing\Router;
use TDarkCoder\Framework\Routing\RouterContract;
use TDarkCoder\Framework\Services\AccessToken\AuthorizeTokens;
use TDarkCoder\Framework\Session\Session;
use TDarkCoder\Framework\Views\View;
use TDarkCoder\Framework\Views\ViewContract;
use Throwable;

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
        } catch (Throwable $exception) {
            echo $this->renderError($exception);

            exit(1);
        }
    }

    public function run(): never
    {
        try {
            echo $this->router->resolve();
        } catch (Throwable $exception) {
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

        if (
            !$user instanceof Model
            || !class_uses($user, AuthorizeTokens::class)
            || !$this->session->has(SessionKeys::AuthToken->value)
        ) {
            return;
        }

        $this->user = $user->authorizeWithToken($this->session->get(SessionKeys::AuthToken->value));
    }

    private function renderError(Throwable $exception): string
    {
        if (!$exception instanceof HttpException) {
            $exception = new ServerErrorException(previous: $exception);
        }

        http_response_code($exception->getStatusCode());

        $file = null;

        if (file_exists(basePath("/views/_errors/{$exception->getStatusCode()}.php"))) {
            $file = "_errors/{$exception->getStatusCode()}";
        }

        if (is_null($file) && file_exists(basePath('/views/_errors.php'))) {
            $file = '_errors';
        }

        if (!isset($this->view) || is_null($file)) {
            ob_start();

            include __DIR__ . '/Views/templates/_errors.php';

            return ob_get_clean();
        }

        return $this->view->layout(null)->render($file, compact('exception'));
    }
}
