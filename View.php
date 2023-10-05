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