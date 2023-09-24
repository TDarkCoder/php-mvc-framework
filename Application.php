<?php

namespace TDarkCoder\Framework;

use Exception;

class Application
{
    public static self $app;
    public readonly Database $database;
    public readonly Request $request;
    public readonly Router $router;
    public readonly Session $session;
    public readonly View $view;

    public function __construct(public readonly string $rootPath, public readonly array $config)
    {
        self::$app = $this;

        $this->initializeComponents();
    }

    private function initializeComponents(): void
    {
        $this->database = new Database();
        $this->request = new Request();
        $this->session = new Session();
        $this->router = new Router($this->request);
        $this->view = new View();
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

        exit;
    }
}