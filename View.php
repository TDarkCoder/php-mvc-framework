<?php

namespace TDarkCoder\Framework;

use Exception;

class View
{
    public const DEFAULT_TITLE = 'Home';

    private string $title = self::DEFAULT_TITLE;
    private string $layout = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function render(string $view, array $params): string
    {
        $view = $this->renderView($view, $params);

        if (!empty($this->layout)) {
            $layout = $this->renderLayout();

            return str_replace('{{content}}', $view, $layout);
        }

        return $view;
    }

    public function renderError(Exception $exception): string
    {
        $file = null;

        if (file_exists(basePath("/views/_errors/{$exception->getCode()}.php"))) {
            $file = "_errors/{$exception->getCode()}";
        }

        if (is_null($file) && file_exists(basePath('/views/_errors.php'))) {
            $file = '_errors';
        }

        if (is_null($file)) {
            ob_start();

            include_once __DIR__ . '/Views/_errors.php';

            return ob_get_clean();
        }

        return $this->render($file, compact('exception'));
    }

    private function renderLayout(): bool|string
    {
        ob_start();

        include_once basePath("/views/layouts/$this->layout.php");

        return ob_get_clean();
    }

    private function renderView(string $view, array $params): bool|string
    {
        ob_start();

        extract($params);

        include_once basePath("/views/$view.php");

        return ob_get_clean();
    }
}