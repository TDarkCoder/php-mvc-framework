<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Enums\SessionKeys;
use Exception;
use TDarkCoder\Framework\Services\AccessToken\AuthorizeTokens;

class Application
{
    public static self $app;
    public readonly Database $database;
    public readonly Request $request;
    public readonly Router $router;
    public readonly Session $session;
    public ?Model $user = null;
    public readonly View $view;

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

    private function initializeComponents(): void
    {
        $this->database = new Database();
        $this->request = new Request();
        $this->session = new Session();
        $this->router = new Router($this->request);
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
            || !$token = $this->session->get(SessionKeys::Token->value)) {
            return;
        }

        $this->user = $user->authorizeWithToken($token);

        if (!$this->user) {
            $this->session->unset(SessionKeys::Token->value);
        }
    }

    public function run(): never
    {
        try {
            echo $this->router->resolve();
        } catch (Exception $exception) {
            echo $this->view->render('_errors', [
                'exception' => $exception,
            ]);
        }

        return $this->view->render($file, compact('exception'));
    }
}