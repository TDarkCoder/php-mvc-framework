<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Contracts\Authenticatable;
use TDarkCoder\Framework\Contracts\Router as RouterContract;
use TDarkCoder\Framework\Contracts\View as ViewContract;
use TDarkCoder\Framework\Database\Database;
use TDarkCoder\Framework\Database\Model;
use TDarkCoder\Framework\Enums\SessionKeys;
use TDarkCoder\Framework\Exceptions\HttpException;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;
use TDarkCoder\Framework\Routing\Router;
use TDarkCoder\Framework\Session\Session;
use TDarkCoder\Framework\Views\View;
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

        $this->database = new Database();
        $this->request = new Request();
        $this->session = new Session();
        $this->router = new Router();
        $this->view = new View();
    }

    /**
     * Resolve the current request into a response without sending it.
     */
    public function handle(): Response
    {
        try {
            $this->initializeUser();

            return $this->router->resolve();
        } catch (Throwable $exception) {
            return $this->renderError($exception);
        }
    }

    public function run(): void
    {
        $this->handle()->send();
    }

    private function initializeUser(): void
    {
        $class = config('auth.model') ?? config('user');

        if (!$class || !$this->session->has(SessionKeys::AuthToken->value)) {
            return;
        }

        $user = new $class();

        if (!$user instanceof Model || !$user instanceof Authenticatable) {
            return;
        }

        $this->user = $user->authorizeWithToken($this->session->get(SessionKeys::AuthToken->value));
    }

    private function renderError(Throwable $exception): Response
    {
        if (!$exception instanceof HttpException) {
            $exception = new ServerErrorException(previous: $exception);
        }

        if ($this->request->wantsJson()) {
            return Response::json(['message' => $exception->getMessage()], $exception->getStatusCode());
        }

        return new Response($this->renderErrorView($exception), $exception->getStatusCode());
    }

    private function renderErrorView(HttpException $exception): string
    {
        $status = $exception->getStatusCode();

        $view = new View();

        foreach (["_errors/$status", '_errors'] as $name) {
            if ($view->exists($name)) {
                return $view->render($name, compact('exception'));
            }
        }

        ob_start();

        include __DIR__ . '/Views/templates/_errors.php';

        return ob_get_clean();
    }
}
